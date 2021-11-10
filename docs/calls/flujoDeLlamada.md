# Flujo de llamada

## Descripción
A lo largo de una llamada la centralita va generando diferentes eventos, algunos des estos eventos son relevantes para la codificación de las llamadas. Uno de los objetivos del módulo de llamadas es capturar estos eventos y codificarlos de manera que podamos realizar las acciones pertinentes.

Estos eventos vienen mediante un proceso socket para permitir la optención sin ningún retraso.

## Eventos

### NewChannel
####  *Descripción*
Cada vez que se genera un canal salta un evento de este tipo con la información relativa al canal.

#### *Consideraciones*
- *Códigos en las llamadas*: Si al crear un canal el destino tiene un * no generaremos la llamada, ya que si lo hacemos no tenemos un hangup para cerrar la llamada y queda abierta
#### *Secuencia*

### BridgeEnter
### QueueCallerJoin
### DialBegin
####  *Descripción*
Este evento salta cuando se empieza a marcar una llamada
#### *Consideraciones*

#### *Secuencia*
- Obtenemos el canal
- Obtenemos la llamada
- Actualiamos el estado del canal
- Si no tenemos llamante lo tenemos que actualizar

### DialEnd
#### *Descripción*
Este evento salta cuando se finaliza el proceso de marcar (en este punto sabremos si la llamada fue antendida o no)
#### *Consideraciones*
- Cuando finalizamos una dialend dentro del flujo de la llamada es importante que miramos si estamos conectando con una llamada del troncal o entre extensiones. Ya que si hacemos una transferencia puede que estemos sea la conversación entre usuarios y finalmente no se conecte la llamada.
- Cuando tenemos una llamada que se origina con un origin, no tenemos channel, sólo tenemos destchannel
- Si tenemos una llamada principal y tenemos channel y destchannel con internas, quiere decir que estamos realizando una transferencia, con lo que tendremos que asignar este tiempo a una llamada interna nueva.
#### *Secuencia*
- Obtenemos el canal
- Obtenemos la llamada
- Actualiamos el estado del canal
- Si no tenemos llamante lo tenemos que actualizar

### Hangup
####  *Descripción*
Este evento salta cuando un canal se cierra
#### *Consideraciones*
- *Tipo de canal que cerraremos*: Podemos cerrar un canal que apunte un troncal, que apunte una extensió local o que apunte una extensión con el protocolo PJSIP o SIP.
#### *Secuencia*
- Obtenemos el canal en la base de datos 
- Obtenemos las llamada identificada por el linkedid
- Si el canal es de una extensión finalizamos el registro de la extensión
- Si no tenemos ningún canal más en la llamada lo finalizamos

### Hold
### Unhold
### AttendedTransfer
####  *Descripción*
Este evento salta cuando se realiza una transferencia atendida a otra extensión
#### *Consideraciones*
- *Obtención de la extensión*: Obtenemos la extensión por el canal y no por otro campo por si tenemos una llamada saliente y tenemos el CID en ese punto.
#### *Secuencia*
- Obtenemos la extensión por el canal `$originExtension = get_channel_name($this->data['OrigTransfererChannel']);`
- Obtenemos la llamada identificada por el linkedid `getCurrentCallByLinkedid($this->data['TransferTargetLinkedid'], $this->data['company_id']);`
- Finalizamos el tramo del otro usuario si existe `finishCurrentCallUserByExtension($currentCall, $this->data['OrigTransfererCallerIDNum'], $this->data['start']);`
- Asignamos el nuevo usuario a la llamada `startCurrentCallUser($currentCall, $this->data['OrigTransfererConnectedLineNum'], $this->data['start']);`

### Pickup
####  *Descripción*
Este evento aparece cuando una extensión captura otra llamada

#### *Consideraciones*
#### *Secuencia*
