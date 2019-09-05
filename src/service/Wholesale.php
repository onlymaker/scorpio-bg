<?php

namespace service;

use db\SqlMapper;

class Wholesale extends \Prefab
{
    function saveAndNotify(string $email)
    {
        if ($email) {
            $email = strtolower($email);
            $wholesale = new SqlMapper('wholesale');
            $wholesale->load(['email=?', $email]);
            if ($wholesale->dry()) {
                $wholesale['email'] = $email;
                $wholesale->save();
                $smtp = new \SMTP(
                    'hwsmtp.exmail.qq.com',
                    465,
                    'ssl',
                    'service@onlymaker.com',
                    \Base::instance()->get('EMAIL.SECRET')
                );
                $smtp->set('From', 'service@onlymaker.com');
                $smtp->set('To', '<steven@onlymaker.com>');
                $smtp->set('Bcc', '<jibo@onlymaker.com>');
                $smtp->set('Content-Type', 'text/html; charset=UTF-8');
                $smtp->set('Subject', 'Wholesale Contact');
                writeLog('Send out result: ' . $smtp->send($email));
            }
        }
    }
}
