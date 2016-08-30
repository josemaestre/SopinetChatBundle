## Servicio InterfaceHelper

Todas las funciones disponibles definidas más arriba, en el controlador por defecto, lo están también como servicios, con la misma nomenclatura 
y con los mismos parámetros como servicios.

El servicio principal está cargado por defecto y su nombre es: "sopinet_chatbundle_interfacehelper"
 
Por tanto, accediendo a él, se podrán utilizar las funciones anteriores. Éstas reciben un parámetro de tipo Request, con los datos necesario.

De ese modo, se podría implementar un controlador de Chat específico en cada aplicación, que aplicase una lógica concreta y utilizase los servicios
necesarios en los momentos que su lógica requiriese.

## Ejemplo: Mandar mensaje si no has mandado más de 100

Imaginemos un ejemplo sencillo: queremos que nuestro sendMessage sólo mande un mensaje si el aún no nos hemos excedido de mandar 100 o más
mensajes. Implementaremos un Controlador propio, con una función propia donde se haga dicha comprobación:

```php
class MyCustomApiChatController extends FOSRestController
{
    /** Aquí vendría la documentación api/doc, importante hacerla **/
    public function sendMessageAction(Request $request)
    {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');

        /** Interface de LoginHelper genérica **/
        $loginHelper = $this->get('sopinet_login_helper');
        
        /** ApiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        /** @var $user */
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            return $apiHelper->responseDenied($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        if (count($user->getMessages() > 100) {
            return $apiHelper->responseDenied("Error limit messages", Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $message = $interfaceHelper->sendMessage($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($message, Message::GROUP_CREATE);        
    }
}
```

[Volver al índice](README.md)
