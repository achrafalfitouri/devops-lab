<?php

namespace App\Repositories\Contracts;

interface StaticRepositoryInterface
{
    public function getAllData();
    public function getAllDataUser();
    public function getAllDataTransaction();
    public function getDocuments();
    public function getContact();
    public function getPaymentType();
    public function getDataTest();
    public function getClienFortEmailSelect ();
    public function getClientContactForEmailSelect ($id);
    public function getClientDocumentCodesForEmailSelect ($id, $type);
    public function getTemplate();
    public function getRecoveriesForPayment($paymentTypeId = null, $clientId = null, $recoveryId = null);


}
