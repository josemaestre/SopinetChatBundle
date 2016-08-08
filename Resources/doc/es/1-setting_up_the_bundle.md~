# Requisitos:

- El sistema hace uso de Application\Sonata\UserBundle\Entity\User.php, por tanto, éste debe ser el sistema para Usuarios por defecto.
- El sistema hace uso de RMS_PUSH_NOTIFICATION
- El sistema hace uso del protocolo de interfaz de Login estándar, definido en Sopinet: esto es, un servicio "sopinet_login_helper", que dispoga de una función públia getUser(Request $request), la cual devuelva un objeto de tipo User. Más información aquí: http://redmine.sopinet.es:3000/projects/symfony-development/wiki/Requisitos_para_la_implementaci%C3%B3n_del_checkeo_de_Usuario_desde_API
- Es probable que se estén utilizando otros bundles (como fos_rest_bundle) y la gestión de dependencias en composer.json no esté debidamente especificada, pero estos bundles son típicos que utilizamos en BasicSymfony.

# Instalación básica:

## Instalación del bundle desde el repositorio privado:

```
"require": {
  "sopinet/chatbundle": "dev-master",
},
"repositories": [
  {
    "type": "vcs",
    "url":  "git@gitlab.sopinet.es:hidabe/chatbundle.git"
  }
],
```

## Añadimos a AppKernel:

```php
new Sopinet\ChatBundle\SopinetChatBundle(),
```

## Configuración de RMS_PUSH_NOTIFICATION:

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

### Configuración de sopinet_chat (No es necesaria, si no se indica se activará por defecto):

```
sopinet_chat:
    anyType: false (default)
    enabledAndroid: true (default)
    enabledIOS: true (default)
```

## Integración con User

En nuestra entidad user habrá que incorporar un trait:
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

[Volver al índice](../../../README.md)
