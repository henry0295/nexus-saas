@component('mail::message')
# Verifica tu dirección de correo

Hola,

Has agregado **{{ $sender_email }}** como remitente en NexusSaaS. Para poder usar este email para enviar campañas, necesitas verificarlo haciendo clic en el botón de abajo.

@component('mail::button', ['url' => $verification_url])
Verificar Email
@endcomponent

Si no solicitaste agregar este email, puedes ignorar este mensaje.

Gracias,<br>
**El equipo de NexusSaaS**
@endcomponent
