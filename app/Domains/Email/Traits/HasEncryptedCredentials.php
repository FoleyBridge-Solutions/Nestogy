<?php

namespace App\Domains\Email\Traits;

use Illuminate\Support\Facades\Crypt;

trait HasEncryptedCredentials
{
    public function setImapPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['imap_password'] = Crypt::encryptString($value);
        }
    }

    public function getImapPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setSmtpPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['smtp_password'] = Crypt::encryptString($value);
        }
    }

    public function getSmtpPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setOauthAccessTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['oauth_access_token'] = Crypt::encryptString($value);
        }
    }

    public function getOauthAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setOauthRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['oauth_refresh_token'] = Crypt::encryptString($value);
        }
    }

    public function getOauthRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
