<?php
/**
 * Custplace.com
 *
 * @author    Custplace <support@custplace.com> - https://fr.custplace.com
 * @copyright THIRD VOICE 2023 - https://fr.custplace.com
 * @license   see file: LICENSE.txt
 *
 * @version   2.0.0
 */

require_once dirname(__FILE__) . '/src/Constants/CustplaceConstants.php';

use Custplace\Constants\CustplaceConstants;

class CustplaceApi
{
    private int $idClient;
    private string $apiKey;
    private string $apiEndpoint;

    /**
     * Constructor
     *
     * @param int $idClient
     * @param string $apiKey
     * @param string|null $apiEndpoint
     */
    public function __construct(int $idClient, string $apiKey, ?string $apiEndpoint = null)
    {
        $this->idClient = $idClient;
        $this->apiKey = $apiKey;
        $this->apiEndpoint = $apiEndpoint ?: CustplaceConstants::API_ENDPOINT_BASE;
    }

    /**
     * Send invitation to Custplace API
     *
     * @param array $body
     * @return array
     * @throws \Exception
     */
    public function sendInvitation(array $body): array
    {
        $url = $this->apiEndpoint . $this->idClient . '/invitations';
        
        $headers = [
            'Cache-Control: no-cache',
            'Content-type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'User-Agent: PrestaShop/' . _PS_VERSION_,
            'X-Source-Id: 39',
        ];
        
        $curl = curl_init();
        if ($curl === false) {
            throw new \Exception('Failed to initialize cURL');
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        
        curl_close($curl);
        
        if ($response === false) {
            $errorMessage = 'cURL error: ' . $curlError;
            \PrestaShopLogger::addLog(
                'Custplace API Error: cURL failed - ' . $errorMessage,
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
            throw new \Exception($errorMessage);
        }
        
        if ($httpCode >= 400) {
            $errorMessage = 'HTTP error: ' . $httpCode . ' - Response: ' . substr($response, 0, 500);
            \PrestaShopLogger::addLog(
                'Custplace API Error: HTTP ' . $httpCode . ' - ' . $errorMessage,
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
            throw new \Exception('HTTP error: ' . $httpCode);
        }
        
        return $this->parseResponse($response);
    }
    
    /**
     * Parse API response
     *
     * @param string $response
     * @return array
     */
    private function parseResponse(string $response): array
    {
        $responseData = json_decode($response);
        
        $result = [
            'status_order' => CustplaceConstants::STATUS_PENDING,
            'sollicitation_id' => null,
        ];
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            \PrestaShopLogger::addLog(
                'Custplace API Error: Invalid JSON response - ' . json_last_error_msg() . ' - Response: ' . substr($response, 0, 500),
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
            $result['status_order'] = CustplaceConstants::STATUS_ERROR;
            return $result;
        }
        
        if (isset($responseData->code) && $responseData->code !== 'success') {
            \PrestaShopLogger::addLog(
                'Custplace API Error: API returned error code - ' . $responseData->code . ' - Response: ' . substr($response, 0, 500),
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
            $result['status_order'] = CustplaceConstants::STATUS_ERROR;
        } elseif (!empty($responseData->id)) {
            $result['status_order'] = CustplaceConstants::STATUS_SUCCESS;
            $result['sollicitation_id'] = $responseData->id;
        }
        
        return $result;
    }
}
