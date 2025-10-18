<?php
return [
    'host'       => 'sandbox.smtp.mailtrap.io',
    'port'       => 2525, // también puedes usar 25, 465 o 587
    'username'   => '1ed84d72ba3165', // tu usuario de Mailtrap
    'password'   => '8d2ff90d985a01', // tu password de Mailtrap
    'encryption' => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, // TLS
    'from_email' => 'tienda@doncamaron.com',
    'from_name'  => 'Don Camarón Online'
];
