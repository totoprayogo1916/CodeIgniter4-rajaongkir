<?php

/**
 * This file is part of CodeIgniter 4 Rajaongkir.
 *
 * (c) 2022 Toto Prayogo <mail@totoprayogo.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Totoprayogo1916\CodeIgniter\Libraries;

use CodeIgniter\Config\Services;
use Exception;

/**
 * Class Rajaongkir
 * This class provides an interface to interact with the Rajaongkir API for various shipping and logistic services.
 */
class Rajaongkir
{
    // Account types
    public const ACCOUNT_STARTER = 'starter';
    public const ACCOUNT_BASIC   = 'basic';
    public const ACCOUNT_PRO     = 'pro';

    // Account type for the current instance
    protected $accountType = self::ACCOUNT_STARTER;

    // API key for authentication
    protected $apiKey;

    // Response object for handling responses
    protected $response;

    // Array to hold any errors encountered
    public $errors = [];

    // Supported account types
    protected $supportedAccountTypes = [
        self::ACCOUNT_STARTER,
        self::ACCOUNT_BASIC,
        self::ACCOUNT_PRO,
    ];

    // Supported couriers for each account type
    protected $supportedCouriers = [
        self::ACCOUNT_STARTER => ['jne', 'pos', 'tiki'],
        self::ACCOUNT_BASIC   => ['esl', 'jne', 'pcp', 'pos', 'rpx', 'tiki'],
        self::ACCOUNT_PRO     => [
            'cahaya', 'dse', 'esl', 'expedito*', 'first', 'idl', 'indah', 'j&t', 'jet', 'jne',
            'lion', 'ncs', 'ninja-express', 'pahala', 'pandu', 'pcp', 'pos', 'rex', 'rpx', 'sap',
            'sicepat', 'slis', 'star', 'tiki', 'wahana',
        ],
    ];

    // Supported waybills for each account type
    protected $supportedWayBills = [
        self::ACCOUNT_STARTER => [],
        self::ACCOUNT_BASIC   => ['jne'],
        self::ACCOUNT_PRO     => [
            'dse', 'first', 'j&t', 'jet', 'jne', 'pcp', 'pos', 'rpx', 'sap', 'sicepat', 'tiki', 'wahana',
        ],
    ];

    // List of couriers and their descriptions
    protected $couriersList = [
        'cahaya'    => 'Cahaya Logistik (CAHAYA)',
        'dse'       => '21 Express (DSE)',
        'esl'       => 'Eka Sari Lorena (ESL)',
        'expedito*' => 'Expedito*',
        'first'     => 'First Logistics (FIRST)',
        'indah'     => 'Indah Logistic (INDAH)',
        'j&t'       => 'J&T Express (J&T)',
        'jet'       => 'JET Express (JET)',
        'jne'       => 'Jalur Nugraha Ekakurir (JNE)',
        'ncs'       => 'Nusantara Card Semesta (NCS)',
        'pahala'    => 'Pahala Kencana Express (PAHALA)',
        'pandu'     => 'Pandu Logistics (PANDU)',
        'pcp'       => 'Priority Cargo and Package (PCP)',
        'pos'       => 'POS Indonesia (POS)',
        'rpx'       => 'RPX Holding (RPX)',
        'sap'       => 'SAP Express (SAP)',
        'sicepat'   => 'SiCepat Express (SICEPAT)',
        'slis'      => 'Solusi Express (SLIS)',
        'star'      => 'Star Cargo (STAR)',
        'tiki'      => 'Citra Van Titipan Kilat (TIKI)',
        'wahana'    => 'Wahana Prestasi Logistik (WAHANA)',
    ];

    /**
     * Rajaongkir constructor.
     *
     * @param array|string|null $apiKey      The API key or an array containing 'api_key' and optionally 'account_type'.
     * @param string|null       $accountType The account type (starter, basic, pro).
     */
    public function __construct($apiKey = null, $accountType = null)
    {
        if (isset($apiKey)) {
            $this->apiKey = is_array($apiKey) ? ($apiKey['api_key'] ?? null) : $apiKey;
            $accountType  = is_array($apiKey) ? ($apiKey['account_type'] ?? $accountType) : $accountType;
        }

        if (isset($accountType)) {
            $this->setAccountType($accountType);
        }
    }

    /**
     * Set the API key.
     *
     * @param string $apiKey The API key.
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Set the account type.
     *
     * @param string $accountType The account type (starter, basic, pro).
     *
     * @return $this
     *
     * @throws Exception If the account type is invalid.
     */
    public function setAccountType($accountType)
    {
        $accountType = strtolower($accountType);

        if (in_array($accountType, $this->supportedAccountTypes, true)) {
            $this->accountType = $accountType;
        } else {
            throw new Exception('Rajaongkir: Invalid Account Type');
        }

        return $this;
    }

    /**
     * Make a request to the Rajaongkir API.
     *
     * @param string $path   The API endpoint path.
     * @param array  $params The query parameters.
     * @param string $type   The request method (GET, POST).
     *
     * @return mixed The API response or false if an error occurred.
     */
    protected function request($path, $params = [], $type = 'GET')
    {
        $apiUrl = 'https://api.rajaongkir.com';

        switch ($this->accountType) {
            case self::ACCOUNT_PRO:
                $apiUrl = 'https://pro.rajaongkir.com';
                $path   = 'api/' . $path;
                break;

            case self::ACCOUNT_BASIC:
                $path = 'basic/' . $path;
                break;

            case self::ACCOUNT_STARTER:
            default:
                $path = 'starter/' . $path;
                break;
        }

        $client = Services::curlrequest();
        $uri    = $apiUrl . '/' . $path;

        if ($type === 'POST') {
            $options['form_params'] = $params;
        } else {
            $options['query'] = $params;
        }

        $client->setHeader('key', $this->apiKey);
        $this->response = $client->request($type, $uri, $options);

        $body   = json_decode($this->response->getBody(), true, 512, JSON_THROW_ON_ERROR)['rajaongkir'];
        $status = $body['status'];

        if ($status['code'] === 200) {
            return $body['results'] ?? $body['result'] ?? false;
        }

        $this->errors[$status['code']] = $status['description'];

        return false;
    }

    /**
     * Get the list of couriers.
     *
     * @return array The list of couriers.
     */
    public function getCouriersList()
    {
        return $this->couriersList;
    }

    /**
     * Get the list of provinces.
     *
     * @return mixed The list of provinces or false if an error occurred.
     */
    public function getProvinces()
    {
        return $this->request('province');
    }

    /**
     * Get details of a specific province.
     *
     * @param int $idProvince The province ID.
     *
     * @return mixed The province details or false if an error occurred.
     */
    public function getProvince(int $idProvince)
    {
        return $this->request('province', ['id' => $idProvince]);
    }

    /**
     * Get the list of cities, optionally filtered by province ID.
     *
     * @param int|null $idProvince
     *
     * @return mixed The list of cities or false if an error occurred.
     */
    public function getCities(?int $idProvince = null)
    {
        $params = isset($idProvince) ? ['province' => $idProvince] : [];

        return $this->request('city', $params);
    }

    /**
     * Get details of a specific city.
     *
     * @param int $idCity The city ID.
     *
     * @return mixed The city details or false if an error occurred.
     */
    public function getCity(int $idCity)
    {
        return $this->request('city', ['id' => $idCity]);
    }

    /**
     * Retrieves information about subdistricts.
     *
     * This function fetches data about subdistricts based on the provided city ID
     * and optionally a specific subdistrict ID.
     *
     * @param int $idCity The ID of the city to retrieve subdistricts for.
     * @param int|null $idSubdistrict (optional) The ID of a specific subdistrict within the city.
     *        If null, all subdistricts for the city will be retrieved.
     *
     * @return mixed
     */
    public function getSubdistricts(int $idCity, ?int $idSubdistrict = null)
    {
    $params = ['city' => $idCity];

    if ($idSubdistrict !== null) {
        $params['id'] = $idSubdistrict;
    }

    return $this->request('subdistrict', $params);
    }

    /**
 * Get the cost of shipping from origin to destination with specific weight and courier.
 *
 * @param array $origin The origin city or subdistrict ID, with the key indicating the type ('city' or 'subdistrict').
 * @param array $destination The destination city, subdistrict, or country ID, with the key indicating the type ('city', 'subdistrict', or 'country').
 * @param mixed $metrics The weight as an integer or an array containing weight and optionally dimensions.
 * @param string $courier The courier code.
 * @return mixed The shipping cost or false if an error occurred.
 */
public function getCost(array $origin, array $destination, $metrics, $courier)
{
    $params = [
        'courier' => strtolower($courier),
        'originType' => strtolower(key($origin)),
        'destinationType' => strtolower(key($destination))
    ];

    // Adjust origin and destination types if needed
    if ($params['originType'] !== 'city') {
        $params['originType'] = 'subdistrict';
    }

    if (!in_array($params['destinationType'], ['city', 'country'], true)) {
        $params['destinationType'] = 'subdistrict';
    }

    // Handle metrics
    if (is_array($metrics)) {
        if (!isset($metrics['weight']) && isset($metrics['length'], $metrics['width'], $metrics['height'])) {
            // Calculate volumetric weight if only dimensions are provided
            $metrics['weight'] = (($metrics['length'] * $metrics['width'] * $metrics['height']) / 6000) * 1000;
        } elseif (isset($metrics['weight'], $metrics['length'], $metrics['width'], $metrics['height'])) {
            // Choose the higher value between actual weight and volumetric weight
            $weight = (($metrics['length'] * $metrics['width'] * $metrics['height']) / 6000) * 1000;
            if ($weight > $metrics['weight']) {
                $metrics['weight'] = $weight;
            }
        }
        foreach ($metrics as $key => $value) {
            $params[$key] = $value;
        }
    } elseif (is_numeric($metrics)) {
        $params['weight'] = $metrics;
    }

    // Account type specific checks and adjustments
    if ($this->accountType === self::ACCOUNT_STARTER) {
        if ($params['destinationType'] === 'country') {
            $this->errors[301] = 'Unsupported International Destination. Tipe akun starter tidak mendukung pengecekan destinasi internasional.';
            return false;
        }
        if ($params['originType'] === 'subdistrict' || $params['destinationType'] === 'subdistrict') {
            $this->errors[302] = 'Unsupported Subdistrict Origin-Destination. Tipe akun starter tidak mendukung pengecekan ongkos kirim sampai kecamatan.';
            return false;
        }
        if (!isset($params['weight']) && isset($params['length'], $params['width'], $params['height'])) {
            $this->errors[304] = 'Unsupported Dimension. Tipe akun starter tidak mendukung pengecekan biaya kirim berdasarkan dimensi.';
            return false;
        }
        if (isset($params['weight']) && $params['weight'] > 30000) {
            $this->errors[305] = 'Unsupported Weight. Tipe akun starter tidak mendukung pengecekan biaya kirim dengan berat lebih dari 30000 gram (30kg).';
            return false;
        }
        if (!in_array($params['courier'], $this->supportedCouriers[$this->accountType], true)) {
            $this->errors[303] = 'Unsupported Courier. Tipe akun starter tidak mendukung pengecekan biaya kirim dengan kurir ' . $this->couriersList[$courier] . '.';
            return false;
        }
    } elseif ($this->accountType === self::ACCOUNT_BASIC) {
        if ($params['originType'] === 'subdistrict' || $params['destinationType'] === 'subdistrict') {
            $this->errors[302] = 'Unsupported Subdistrict Origin-Destination. Tipe akun basic tidak mendukung pengecekan ongkos kirim sampai kecamatan.';
            return false;
        }
        if (!isset($params['weight']) && isset($params['length'], $params['width'], $params['height'])) {
            $this->errors[304] = 'Unsupported Dimension. Tipe akun basic tidak mendukung pengecekan biaya kirim berdasarkan dimensi.';
            return false;
        }
        if (isset($params['weight']) && $params['weight'] > 30000) {
            $this->errors[305] = 'Unsupported Weight. Tipe akun basic tidak mendukung pengecekan biaya kirim dengan berat lebih dari 30000 gram (30kg).';
            return false;
        }
        if (isset($params['weight']) && $params['weight'] < 30000) {
            unset($params['length'], $params['width'], $params['height']);
        }
        if (!in_array($params['courier'], $this->supportedCouriers[$this->accountType], true)) {
            $this->errors[303] = 'Unsupported Courier. Tipe akun basic tidak mendukung pengecekan biaya kirim dengan kurir ' . $this->couriersList[$courier] . '.';
            return false;
        }
    }

    // Set origin and destination values
    $params['origin']      = $origin[key($origin)];
    $params['destination'] = $destination[key($destination)];

    // Determine the path based on destination type
    $path = key($destination) === 'country' ? 'internationalCost' : 'cost';

    // Make the request
    return $this->request($path, $params, 'POST');
}


    /**
     * Get the waybill tracking information for a specific courier.
     *
     * @param string $waybill The waybill number.
     * @param string $courier The courier code.
     *
     * @return mixed The waybill tracking information or false if an error occurred.
     */
    public function getWayBill($waybill, $courier)
    {
        if ($this->accountType === self::ACCOUNT_STARTER) {
            $this->errors[301] = 'Unsupported Way Bill Request.';

            return false;
        }

        return $this->request('waybill', ['waybill' => $waybill, 'courier' => $courier], 'POST');
    }

    /**
     * Get the currency for the current account type.
     *
     * @return mixed The currency information or false if an error occurred.
     */
    public function getCurrency()
    {
        if ($this->accountType !== self::ACCOUNT_STARTER) {
            return $this->request('currency');
        }

        $this->errors[301] = 'Unsupported Get Currency. Tipe akun starter tidak mendukung pengecekan currency.';

        return false;
    }
}
