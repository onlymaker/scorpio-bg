<?php

namespace service;

use db\SqlMapper;
use Httpful\Mime;
use Httpful\Request;

class Fulfill extends \Prefab
{
    const LOCATION_ID = '16372236';
    const CHINA_POST = 'China Post';
    const YUN_EXPRESS = 'YunExpress';
    const CARRIER = [
        '中外运(A)' => self::YUN_EXPRESS,
        '云途(B)' => self::YUN_EXPRESS,
        'U包(C)' => self::CHINA_POST,
        'E特快(G)' => self::CHINA_POST,
    ];

    function process($data)
    {
        $content = '';
        /*
            ['traceId'] => XX191017096
            ['original'] => OM1884S
            ['sku'] => P90206B
            ['size'] => US13
            ['carrier'] => E特快
            ['trackingNumber'] => EV906802870CN
        */
        $shopifyOrder = new SqlMapper('shopify_order');
        foreach ($data as $item) {
            $line = implode(', ', $item);
            $name = substr($item['original'], 2);
            $filter = ['order_name=? and sku=? and size=? and fulfillment_status=?', $name, $item['sku'], $item['size'], ''];
            $count = $shopifyOrder->count($filter);
            if ($count > 1) {
                $line .=  ': <div style="color:red">Please fulfill it manually</div>';
            } else if ($count == 1) {
                $shopifyOrder->load($filter);
                $line .=  ': <div style="color:green">Processed ' . $this->setup(
                    $shopifyOrder['order_id'],
                    $shopifyOrder['line_item_id'],
                    $item['carrier'],
                    $item['trackingNumber'],
                    ) . '</div>';
            } else {
                $line .= ': <div style="color:blue">Nothing to do</div>';
            }
            $smtp = new \SMTP(
                'hwsmtp.exmail.qq.com',
                465,
                'ssl',
                'service@onlymaker.com',
                \Base::instance()->get('EMAIL.SECRET')
            );
            $content .= $line;
        }
        $smtp->set('From', 'service@onlymaker.com');
        $smtp->set('To', '<pino@onlymaker.com>');
        $smtp->set('Bcc', '<jibo@onlymaker.com>,<steven@onlymaker.com>');
        $smtp->set('Content-Type', 'text/html; charset=UTF-8');
        $smtp->set('Subject', 'Fulfill Notification');
        writeLog('Send out result: ' . $smtp->send($content));
    }

    function setup($orderId, $itemId, $carrier, $trackingNumber)
    {
        try {
            $trackingCompany = self::CARRIER[$carrier] ?? $carrier;
            $params = [
                'fulfillment' => [
                    'location_id' => self::LOCATION_ID,
                    'tracking_number' => $trackingNumber,
                    'line_items' => [
                        [
                            'id' => $itemId,
                        ]
                    ],
                ]
            ];
            /*switch ($trackingCompany) {
                case self::CHINA_POST:
                    $params['fulfillment']['tracking_url'] = 'http://www.ems.com.cn/english.html';
                    break;
                case self::YUN_EXPRESS:
                    $params['fulfillment']['tracking_company'] = $trackingCompany;
                    break;
                default:
                    $params['fulfillment']['tracking_url'] = 'https://www.17track.net/en';
            }*/
            $params['fulfillment']['tracking_url'] = 'https://www.17track.net/en';
            $api = \Base::instance()->get('SHOPIFY_ANALYTICS') . "/admin/api/2019-07/orders/$orderId/fulfillments.json";
            writeLog($api);
            usleep(500000);
            $response = Request::post($api)
                ->sendsType(Mime::JSON)
                ->body(json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                ->send();
            return  $response->code;
        } catch (\Throwable $t) {
            return $t->getTraceAsString();
        }
        return 'unexpected end';
    }
}
