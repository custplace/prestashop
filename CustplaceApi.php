<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class CustplaceApi
{
    protected const ENDPOINT_BASE = 'https://apis.custplace.com/v3/';

    protected $idClient;
    protected $apiKey;

    public function __construct($id_client, $api_key)
    {
        $this->idClient = $id_client;
        $this->apiKey = $api_key;
    }

    public function sendInvitation($body)
    {
        $url = self::ENDPOINT_BASE . $this->idClient . '/invitations';
        $header = [
            'Cache-Control: no-cache',
            'Content-type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $resp = curl_exec($curl);
        $resp_json = json_decode($resp);
        $response = [
            'status_order' => 'pending',
            'sollicitation_id' => null,
        ];
        if (isset($resp_json->id)) {
            $response['sollicitation_id'] = $resp_json->id;
        }
        if ($resp_json->code != 'success') {
            $response['status_order'] = 'error';
        } else {
            $response['status_order'] = 'success';
        }
        curl_close($curl);

        return $response;
    }
}
