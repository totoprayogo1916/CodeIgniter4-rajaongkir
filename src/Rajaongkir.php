<?php

/**
 * This file is part of CodeIgniter 4 Rajaongkir.
 *
 * (c) 2022 Toto Prayogo <mail@totoprayogo.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Totoprayogo;

use CodeIgniter\Config\Services;
use Exception;

/**
 * Class Rajaongkir
 */
class Rajaongkir
{
    public const ACCOUNT_STARTER = 'starter';
    public const ACCOUNT_BASIC   = 'basic';
    public const ACCOUNT_PRO     = 'pro';

    /**
     * Rajaongkir::$accountType
     *
     * Rajaongkir Account Type.
     *
     * @var string
     */
    protected $accountType = 'starter';

    /**
     * Rajaongkir::$apiKey
     *
     * Rajaongkir API key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * List of Supported Account Types
     *
     * @var array
     */
    protected $supportedAccountTypes = [
        'starter',
        'basic',
        'pro',
    ];

    /**
     * Supported Couriers
     *
     * @var array
     */
    protected $supportedCouriers = [
        'starter' => [
            'jne',
            'pos',
            'tiki',
        ],
        'basic' => [
            'esl',
            'jne',
            'pcp',
            'pos',
            'rpx',
            'tiki',
        ],
        'pro' => [
            'cahaya',
            'dse',
            'esl',
            'expedito*',
            'first',
            'idl',
            'indah',
            'j&t',
            'jet',
            'jne',
            'lion',
            'ncs',
            'ninja-express',
            'pahala',
            'pandu',
            'pcp',
            'pos',
            'rex',
            'rpx',
            'sap',
            'sicepat',
            'slis',
            'star',
            'tiki',
            'wahana',
        ],
    ];

    /**
     * Rajaongkir::$supportedWaybills
     *
     * Rajaongkir supported couriers waybills.
     *
     * @var array
     */
    protected $supportedWayBills = [
        'starter' => [],
        'basic'   => [
            'jne',
        ],
        'pro' => [
            'dse',
            'first',
            'j&t',
            'jet',
            'jne',
            'pcp',
            'pos',
            'rpx',
            'sap',
            'sicepat',
            'tiki',
            'wahana',
        ],
    ];

    /**
     * Rajaongkir::$couriersList
     *
     * Rajaongkir courier list.
     *
     * @var array
     */
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
     * Rajaongkir::$response
     *
     * Rajaongkir response.
     *
     * @var mixed
     */
    protected $response;

    /**
     * @var list<mixed>
     */
    public $errors = [];

    /**
     * Rajaongkir::__construct
     *
     * @param mixed|null $apiKey
     * @param mixed|null $accountType
     *
     * @throws Exception
     */
    public function __construct($apiKey = null, $accountType = null)
    {
        if (isset($apiKey)) {
            if (is_array($apiKey)) {
                if (isset($apiKey['api_key'])) {
                    $this->apiKey = $apiKey['api_key'];
                }

                if (isset($apiKey['account_type'])) {
                    $accountType = $apiKey['account_type'];
                }
            } elseif (is_string($apiKey)) {
                $this->apiKey = $apiKey;
            }
        }

        if (isset($accountType)) {
            $this->setAccountType($accountType);
        }
    }

    /**
     * Rajaongkir::setApiKey
     *
     * Set Rajaongkir API Key.
     *
     * @param string $apiKey Rajaongkir API Key
     *
     * @return static
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Rajaongkir::setAccountType
     *
     * Set Rajaongkir account type.
     *
     * @param string $accountType RajaOngkir Account Type, can be starter, basic or pro
     *
     * @return static
     *
     * @throws Exception
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
     * Rajaongkir::request
     *
     * Curl request API caller.
     *
     * @param string $path
     * @param array  $params
     * @param string $type
     *
     * @return array|bool Returns FALSE if failed.
     */
    protected function request($path, $params = [], $type = 'GET')
    {
        $apiUrl = 'https://api.rajaongkir.com';

        switch ($this->accountType) {
            default:
            case 'starter':
                $path = 'starter/' . $path;
                break;

            case 'basic':
                $path = 'basic/' . $path;
                break;

            case 'pro':
                $apiUrl = 'https://pro.rajaongkir.com';
                $path   = 'api/' . $path;
                break;
        }

        $client = Services::curlrequest();
        $uri    = $apiUrl . '/' . $path;

        $client->setHeader('key', $this->apiKey);

        $this->response = $client->request($type, $uri, [
            'query' => $params,
        ]);

        $getBody = json_decode($this->response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $body    = $getBody['rajaongkir'];
        $status  = $body['status'];

        if ($status['code'] === 200) {
            if (isset($body['results'])) {
                if ((is_countable($body['results']) ? count($body['results']) : 0) === 1 && isset($body['results'][0])) {
                    return $body['results'][0];
                }

                if ((is_countable($body['results']) ? count($body['results']) : 0) > 0) {
                    return $body['results'];
                }
            } elseif (isset($body['result'])) {
                return $body['result'];
            }
        } else {
            $this->errors[$status['code']] = $status['description'];
        }

        return false;
    }

    /**
     * Rajaongkir::getCouriersList
     *
     * Get list of supported couriers.
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getCouriersList()
    {
        return $this->couriersList;
    }

    /**
     * Rajaongkir::getProvinces
     *
     * Get list of provinces.
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getProvinces()
    {
        return $this->request('province');
    }

    /**
     * Rajaongkir::getProvince
     *
     * Get detail of single province.
     *
     * @param int $idProvince Province ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getProvince($idProvince)
    {
        return $this->request('province', ['id' => $idProvince]);
    }

    /**
     * Rajaongkir::getCities
     *
     * Get list of province cities.
     *
     * @param int $idProvince Province ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getCities($idProvince = null)
    {
        $params = [];

        if (null !== $idProvince) {
            $params['province'] = $idProvince;
        }

        return $this->request('city', $params);
    }

    /**
     * Rajaongkir::getCity
     *
     * Get detail of single city.
     *
     * @param int $idCity City ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getCity($idCity)
    {
        return $this->request('city', ['id' => $idCity]);
    }

    /**
     * Rajaongkir::getSubdistricts
     *
     * Get list of city subdisctricts.
     *
     * @param int $idCity City ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getSubdistricts($idCity)
    {
        if ($this->accountType === 'starter') {
            $this->errors[302] = 'Unsupported Subdistricts Request. Tipe akun starter tidak mendukung hingga tingkat kecamatan.';

            return false;
        }

        if ($this->accountType === 'basic') {
            $this->errors[302] = 'Unsupported Subdistricts Request. Tipe akun basic tidak mendukung hingga tingkat kecamatan.';

            return false;
        }

        return $this->request('subdistrict', ['city' => $idCity]);
    }

    /**
     * Rajaongkir::getSubdistrict
     *
     * Get detail of single subdistrict.
     *
     * @param int $idSubdistrict Subdistrict ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getSubdistrict($idSubdistrict)
    {
        if ($this->accountType === 'starter') {
            $this->errors[302] = 'Unsupported Subdistricts Request. Tipe akun starter tidak mendukung hingga tingkat kecamatan.';

            return false;
        }

        if ($this->accountType === 'basic') {
            $this->errors[302] = 'Unsupported Subdistricts Request. Tipe akun basic tidak mendukung hingga tingkat kecamatan.';

            return false;
        }

        return $this->request('subdistrict', ['id' => $idSubdistrict]);
    }

    /**
     * Rajaongkir::getInternationalOrigins
     *
     * Get list of supported international origins.
     *
     * @param int $idProvince Province ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getInternationalOrigins($idProvince = null)
    {
        if ($this->accountType === 'starter') {
            $this->errors[301] = 'Unsupported International Origin Request. Tipe akun starter tidak mendukung tingkat international.';

            return false;
        }

        $params = [];

        if (isset($idProvince)) {
            $params['province'] = $idProvince;
        }

        return $this->request('v2/internationalOrigin', $params);
    }

    /**
     * Rajaongkir::getInternationalOrigin
     *
     * Get list of supported international origins by city and province.
     *
     * @param int $idCity     City ID
     * @param int $idProvince Province ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getInternationalOrigin($idCity = null, $idProvince = null)
    {
        if ($this->accountType === 'starter') {
            $this->errors[301] = 'Unsupported International Origin Request. Tipe akun starter tidak mendukung tingkat international.';

            return false;
        }

        if (isset($idCity)) {
            $params['id'] = $idCity;
        }

        if (isset($idProvince)) {
            $params['province'] = $idProvince;
        }

        return $this->request('v2/internationalOrigin', $params);
    }

    /**
     * Rajaongkir::getInternationalDestinations
     *
     * Get list of international destinations.
     *
     * @param int $id_country Country ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getInternationalDestinations()
    {
        if ($this->accountType === 'starter') {
            $this->errors[301] = 'Unsupported International Destination Request. Tipe akun starter tidak mendukung tingkat international.';

            return false;
        }

        return $this->request('v2/internationalDestination');
    }

    /**
     * Rajaongkir::getInternationalDestination
     *
     * Get International Destination
     *
     * @param int $idCountry Country ID
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getInternationalDestination($idCountry = null)
    {
        if ($this->accountType === 'starter') {
            $this->errors[301] = 'Unsupported International Destination Request. Tipe akun starter tidak mendukung tingkat international.';

            return false;
        }

        $params = [];

        if (isset($idCountry)) {
            $params['id'] = $idCountry;
        }

        return $this->request('v2/internationalDestination', $params);
    }

    /**
     * Rajaongkir::getCost
     *
     * Get cost calculation.
     *
     * @param array  $origin      City, District or Subdistrict Origin
     * @param array  $destination City, District or Subdistrict Destination
     * @param array  $metrics     Array of Specification
     *                            weight      int     weight in gram (required)
     *                            length      number  package length dimension
     *                            width       number  package width dimension
     *                            height      number  package height dimension
     *                            diameter    number  package diameter
     * @param string $courier     Courier Code
     *
     * @return array|bool Returns FALSE if failed.
     *
     * @see      http://rajaongkir.com/dokumentasi/pro
     *
     * @example
     * $rajaongkir->getCost(
     *      ['city' => 1],
     *      ['subdistrict' => 12],
     *      ['weight' => 100, 'length' => 100, 'width' => 100, 'height' => 100, 'diameter' => 100],
     *      'jne'
     * );
     */
    public function getCost(array $origin, array $destination, $metrics, $courier)
    {
        $params['courier'] = strtolower($courier);

        $params['originType']      = strtolower(key($origin));
        $params['destinationType'] = strtolower(key($destination));

        if ($params['originType'] !== 'city') {
            $params['originType'] = 'subdistrict';
        }

        if (! in_array($params['destinationType'], ['city', 'country'], true)) {
            $params['destinationType'] = 'subdistrict';
        }

        if (is_array($metrics)) {
            if (
                ! isset($metrics['weight'])
                && isset($metrics['length'], $metrics['width'], $metrics['height'])
            ) {
                $metrics['weight'] = (($metrics['length'] * $metrics['width'] * $metrics['height']) / 6000) * 1000;
            } elseif (
                isset($metrics['weight'], $metrics['length'], $metrics['width'], $metrics['height'])
            ) {
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

        if ($this->accountType === 'starter') {
            if ($params['destinationType'] === 'country') {
                $this->errors[301] = 'Unsupported International Destination. Tipe akun starter tidak mendukung pengecekan destinasi international.';

                return false;
            }

            if ($params['originType'] === 'subdistrict' || $params['destinationType'] === 'subdistrict') {
                $this->errors[302] = 'Unsupported Subdistrict Origin-Destination. Tipe akun starter tidak mendukung pengecekan ongkos kirim sampai kecamatan.';

                return false;
            }

            if (
                ! isset($params['weight'])
                && isset($params['length'], $params['width'], $params['height'])
            ) {
                $this->errors[304] = 'Unsupported Dimension. Tipe akun starter tidak mendukung pengecekan biaya kirim berdasarkan dimensi.';

                return false;
            }

            if (isset($params['weight']) && $params['weight'] > 30000) {
                $this->errors[305] = 'Unsupported Weight. Tipe akun starter tidak mendukung pengecekan biaya kirim dengan berat lebih dari 30000 gram (30kg).';

                return false;
            }

            if (! in_array($params['courier'], $this->supportedCouriers[$this->accountType], true)) {
                $this->errors[303] = 'Unsupported Courier. Tipe akun starter tidak mendukung pengecekan biaya kirim dengan kurir ' . $this->couriersList[$courier] . '.';

                return false;
            }
        } elseif ($this->accountType === 'basic') {
            if ($params['originType'] === 'subdistrict' || $params['destinationType'] === 'subdistrict') {
                $this->errors[302] = 'Unsupported Subdistrict Origin-Destination. Tipe akun basic tidak mendukung pengecekan ongkos kirim sampai kecamatan.';

                return false;
            }

            if (
                ! isset($params['weight'])
                && isset($params['length'], $params['width'], $params['height'])
            ) {
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

            if (! in_array($params['courier'], $this->supportedCouriers[$this->accountType], true)) {
                $this->errors[303] = 'Unsupported Courier. Tipe akun basic tidak mendukung pengecekan biaya kirim dengan kurir ' . $this->couriersList[$courier] . '.';

                return false;
            }
        }

        $params['origin']      = $origin[key($origin)];
        $params['destination'] = $destination[key($destination)];

        $path = key($destination) === 'country' ? 'internationalCost' : 'cost';

        return $this->request($path, $params, 'POST');
    }

    /**
     * Rajaongkir::getWaybill
     *
     * Get detail of waybill.
     *
     * @param int         $idWaybill Receipt ID
     * @param string|null $courier   Courier Code
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getWaybill($idWaybill, $courier)
    {
        $courier = strtolower($courier);

        if (in_array($courier, $this->supportedWayBills[$this->accountType], true)) {
            return $this->request('waybill', [
                'key'     => $this->apiKey,
                'waybill' => $idWaybill,
                'courier' => $courier,
            ], 'POST');
        }

        return false;
    }

    /**
     * Rajaongkir::getCurrency
     *
     * Get Rajaongkir currency.
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getCurrency()
    {
        if ($this->accountType !== 'starter') {
            return $this->request('currency');
        }

        $this->errors[301] = 'Unsupported Get Currency. Tipe akun starter tidak mendukung pengecekan currency.';

        return false;
    }

    /**
     * Rajaongkir::getSupportedCouriers
     *
     * Gets list of supported couriers by your account.
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getSupportedCouriers()
    {
        return $this->supportedCouriers[$this->accountType] ?? false;
    }

    /**
     * Rajaongkir::getSupportedWayBills
     *
     * Gets list of supported way bills based on account type.
     *
     * @return array|bool Returns FALSE if failed.
     */
    public function getSupportedWayBills()
    {
        return $this->supportedWayBills[$this->accountType] ?? false;
    }

    /**
     * Rajaongkir::getResponse
     *
     * Get original response object.
     *
     * @param string $offset Response Offset Object
     *
     * @return bool|\O2System\Curl\Response Returns FALSE if failed.
     */
    public function getResponse()
    {
        return $this->response;
    }
}
