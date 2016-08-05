# Instalación básica:

## Instalación del bundle desde el repositorio privado:

```
"require": {
  "sopinet/chatbundle": "dev-master",
}
```

## Añadimos a AppKernel:

```php
new FOS\RestBundle\FOSRestBundle(),
new JMS\SerializerBundle\JMSSerializerBundle(),
new Sopinet\ApiHelperBundle\SopinetApiHelperBundle(),
new FOS\UserBundle\FOSUserBundle(),
new Knp\DoctrineBehaviors\Bundle\DoctrineBehaviorsBundle(),
new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
new RMS\PushNotificationsBundle\RMSPushNotificationsBundle(),
new Sopinet\ChatBundle\SopinetChatBundle(),
new Vich\UploaderBundle\VichUploaderBundle(),
```

## Configuración de Bundles:

### FOSRestAPI

Añadir a config.yml:

```yaml
fos_rest:
    routing_loader:
        default_format: json
```

### FOSUserBundle

Añadir a config.yml

```
fos_user:
    db_driver: orm # other valid values are 'mongodb', 'couchdb' and 'propel'
    firewall_name: main
    user_class: AppBundle\Entity\YourUserEntity
```

Si se está usando SonataUser: 

```
    user_class: Application\Sonata\UserBundle\Entity\User
```

Añadir a security.yml

```
security:
    encoders:
        FOS\UserBundle\Model\UserInterface: bcrypt
```

Añadir a routing.yml:

```yaml
fos_user:
    resource: "@FOSUserBundle/Resources/config/routing/all.xml"
```

Ejecutar desde consola:

```
php bin/console doctrine:schema:update --force
```

### RMSPushNotificationsBundle

```
rms_push_notifications:
    android:
        gcm:
            api_key: %chat_gcm_key%
    ios:
        sandbox: %chat_apn_sandbox%
        pem: %chat_apn_pem%
        passphrase: %chat_apn_passpharase%
```

### VichUploaderBundle

```
vich_uploader:
    db_driver: orm # or mongodb or propel or phpcr
    mappings:
        group_photo:
            namer:              vich_uploader.namer_uniqid
            uri_prefix:         /images/group
            upload_destination: %kernel.root_dir%/../web/images/group
```

Crear la carpeta configurada en uri_prefix

```
mkdir -p web/images/group
```

## Configuración de SopinetChatBundle (opcional):

Todos los parámetros de configuración son opcionales, si no se especifica nada el Bundle funcionará por defecto.

```
sopinet_chat:
    anyType: false (Permite el envío de cualquier tipo de Mensajes incluso no definidos)
    enabledAndroid: true (Activación de funcionamiento en Android)
    enabledIOS: true (Activación de funcionamiento en iOS)
    enabledWeb: false (Activación de funcionamiento en Cliente Web)
    background: false (Activación de funcionamiento a través de una cola de mensajes en Background)
    soundIOS: default (Se puede indicar el nombre del fichero local en iOS que sonará en las notificaciones)
```

enabledWeb, Necesitarás configurar WebSocket si lo quieres activar:
[WebSocket](background/websocket.md)

background, Necesitarás configurar RabbitMQ si lo quieres activar:
[RabbitMQ](background/rabbitMQ.md)


## Integración con tu entidad User

### YourUserEntity

En nuestra entidad User habrá que incorporar un trait:
```php
    use \Sopinet\ChatBundle\Model\UserChat {
        __construct as _traitconstructor;
    }
```
Y en el constructor:
```php
    public function __construct()
    {
        $this->_traitconstructor();
        parent::__construct();
    }
```

### Conexión con UserInterface

Habrá que indicar a SopinetChatBundle qué clase Usuario estamos utilizando, para ello, se realizará la especificación mediante una interface. En el fichero config.yml:

```
doctrine:
    orm:
        resolve_target_entities:
            Sopinet\ChatBundle\Model\UserInterface: AppBundle\Entity\YourUserEntity
```

Si se está usando SonataUser, sería:
```
doctrine:
    orm:
        resolve_target_entities:
            Sopinet\ChatBundle\Model\UserInterface: Application\Sonata\UserBundle\Entity\User
```

## Otras configuraciones obligatorias

### Habilitar el tipo enum

```
doctrine:
    dbal:
        mapping_types:
            enum: string
```

### sopinet_login_helper

Es necesario tener un servicio de Login declarado, de esta forma todas la conexión con el usuario quedará realizada.

La función Helper debe tener un método getUser, que recibe un parámetro Request y devuelve un objeto User

#### Declaración de Servicio
```
    sopinet_login_helper:
        class: AppBundle\Services\LoginHelper
        arguments: ["@service_container", "@doctrine.orm.entity_manager", "@fos_rest.view_handler", "@security.password_encoder"]
```

#### Ejemplo de implementación de función getUser

Sería posible conectar esta implementación con [Guard](http://symfony.com/blog/new-in-symfony-2-8-guard-authentication-component), en un futuro no se descarta utilizar directamente Guard, pero por ahora se podría conectar con Guard implementándolo en la función getUser.

```
class LoginHelper
{
    const ERROR_DATA = "Data error";
    const ERROR_AUTH = "Authentication error";

    public function __construct(Container $container, EntityManager $entityManager, ViewHandler $viewHandler, UserPasswordEncoder $userPasswordEncoder)
    {
        $this->em = $entityManager;
        $this->viewhandler = $viewHandler;
        $this->encoder = $userPasswordEncoder;
        $this->container = $container;
    }

    /**
     * Devuelve el User con los parámetros facilitados
     *
     * @param Request $request
     * @return User
     * @throws Exception
     */
    public function getUser(Request $request) {
        // No implementamos esta función en el ejemplo por carecer de relevación
        $checkParameters = $this->checkAuthenticationParameters($request);
        if (!$checkParameters) {
            throw new Exception(LoginHelper::ERROR_DATA);
        }

        // No implementamos algunas funciones más en el ejemplo por carecer de relevación
        if ($request->get('type') === User::TYPE_CLIENT_FACEBOOK)
            $user = $this->checkCredentialsFacebook($request);
        else
            $user = $this->checkCredentialsNormal($request);

        if (!$user) {
            throw new Exception(LoginHelper::ERROR_AUTH);
        }

        return $user;
    }
}
```

#### Uso desde ChatBundle (no es necesario considerar)

Cuando SopinetChatBundle necesite coger el usuario del sistema se abstraerá del sistema de credenciales y usará el siguiente código:
```
        /** @var LoginHelper $loginHelper */
        $loginHelper = $this->get('sopinet_login_helper');

        /** @var User $user */
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            // Ha ocurrido un error
        }
```

[Volver al índice](README.md)
