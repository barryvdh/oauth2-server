<?php

namespace LeagueTests\Utils;

use League\OAuth2\Server\CryptKey;
use PHPUnit\Framework\TestCase;

class CryptKeyTest extends TestCase
{
    public function testNoFile()
    {
        $this->expectException(\LogicException::class);

        new CryptKey('undefined file');
    }

    public function testKeyCreation()
    {
        $keyFile = __DIR__ . '/../Stubs/public.key';
        $key = new CryptKey($keyFile, 'secret');

        $this->assertEquals('file://' . $keyFile, $key->getKeyPath());
        $this->assertEquals('secret', $key->getPassPhrase());
    }

    public function testKeyFileCreation()
    {
        $keyContent = \file_get_contents(__DIR__ . '/../Stubs/public.key');

        if (!\is_string($keyContent)) {
            $this->fail('The public key stub is not a string');
        }

        $key = new CryptKey($keyContent);

        $this->assertEquals(
            'file://' . \sys_get_temp_dir() . '/' . \sha1($keyContent) . '.key',
            $key->getKeyPath()
        );

        $keyContent = \file_get_contents(__DIR__ . '/../Stubs/private.key.crlf');

        if (!\is_string($keyContent)) {
            $this->fail('The private key (crlf) stub is not a string');
        }

        $key = new CryptKey($keyContent);

        $this->assertEquals(
            'file://' . \sys_get_temp_dir() . '/' . \sha1($keyContent) . '.key',
            $key->getKeyPath()
        );
    }

    public function testUnsupportedKeyType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to read key');

        try {
            // Create the keypair
            $res = \openssl_pkey_new([
                'digest_alg' => 'sha512',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_DSA,
            ]);
            // Get private key
            \openssl_pkey_export($res, $privkey, 'mystrongpassword');
            $path = 'file://' . \sys_get_temp_dir() . '/' . \sha1($privkey) . '.key';

            new CryptKey($privkey, 'mystrongpassword');
        } finally {
            if (isset($path)) {
                @unlink($path);
            }
        }
    }

    public function testECKeyType()
    {
        try {
            // Create the keypair
            $res = \openssl_pkey_new([
                'digest_alg' => 'sha512',
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ]);
            // Get private key
            \openssl_pkey_export($res, $privkey, 'mystrongpassword');

            $key = new CryptKey($privkey, 'mystrongpassword');
            $path = 'file://' . \sys_get_temp_dir() . '/' . \sha1($privkey) . '.key';

            $this->assertEquals($path, $key->getKeyPath());
            $this->assertEquals('mystrongpassword', $key->getPassPhrase());

        } catch (\Throwable $e) {
            $this->fail('The EC key was not created');
        } finally {
            if (isset($path)) {
                @unlink($path);
            }
        }
    }

    public function testRSAKeyType()
    {
        try {
            // Create the keypair
            $res = \openssl_pkey_new([
                 'digest_alg' => 'sha512',
                 'private_key_bits' => 2048,
                 'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            // Get private key
            \openssl_pkey_export($res, $privkey, 'mystrongpassword');

            $key = new CryptKey($privkey, 'mystrongpassword');
            $path = 'file://' . \sys_get_temp_dir() . '/' . \sha1($privkey) . '.key';

            $this->assertEquals($path, $key->getKeyPath());
            $this->assertEquals('mystrongpassword', $key->getPassPhrase());
        } catch (\Throwable $e) {
            $this->fail('The RSA key was not created');
        } finally {
            if (isset($path)) {
                @unlink($path);
            }
        }
    }
}
