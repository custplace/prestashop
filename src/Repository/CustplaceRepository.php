<?php
/**
 * Custplace.com
 *
 * @author    Custplace <support@custplace.com> - https://fr.custplace.com
 * @copyright THIRD VOICE 2023 - https://fr.custplace.com
 * @license   see file: LICENSE.txt
 *
 * @version   1.2.0
 */

namespace Custplace\Repository;

use Custplace\Constants\CustplaceConstants;
use Db;
use DbQuery;

class CustplaceRepository
{
    /**
     * Get custplace records by order ID
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrderId(int $orderId): array
    {
        $query = new DbQuery();
        $query->select('status_order, dateRequest')
            ->from(CustplaceConstants::TABLE_CUSTPLACE)
            ->where('id_order = ' . (int)$orderId);

        return Db::getInstance()->executeS($query) ?: [];
    }

    /**
     * Check if order has successful custplace record
     *
     * @param int $orderId
     * @return bool
     */
    public function hasSuccessfulRecord(int $orderId): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from(CustplaceConstants::TABLE_CUSTPLACE)
            ->where('id_order = ' . (int)$orderId)
            ->where('status_order = \'' . pSQL(CustplaceConstants::STATUS_SUCCESS) . '\'');

        return (int)Db::getInstance()->getValue($query) > 0;
    }

    /**
     * Insert new custplace record
     *
     * @param int $orderId
     * @param string $status
     * @return int|false Last insert ID or false on failure
     */
    public function insert(int $orderId, string $status = CustplaceConstants::STATUS_PENDING)
    {
        $data = [
            'id_order' => (int)$orderId,
            'dateRequest' => date('Y-m-d H:i:s'),
            'status_order' => pSQL($status),
            'invitation_id' => ''
        ];

        if (Db::getInstance()->insert(CustplaceConstants::TABLE_CUSTPLACE, $data)) {
            return Db::getInstance()->Insert_ID();
        }

        return false;
    }

    /**
     * Update custplace record status and invitation ID
     *
     * @param int $id
     * @param string $status
     * @param string|null $invitationId
     * @return bool
     */
    public function updateStatus(int $id, string $status, ?string $invitationId = null): bool
    {
        $data = [
            'status_order' => pSQL($status)
        ];

        if ($invitationId !== null) {
            $data['invitation_id'] = pSQL($invitationId);
        }

        return Db::getInstance()->update(
            CustplaceConstants::TABLE_CUSTPLACE,
            $data,
            'id = ' . (int)$id
        );
    }

    /**
     * Get order information with customer and product details
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderDetails(int $orderId): array
    {
        $query = new DbQuery();
        $query->select('
            ord.id_order,
            ord.reference as ord_ref,
            ord.date_add,
            ord.delivery_date,
            cust.firstname,
            cust.lastname,
            cust.email,
            prod.id_product,
            prod.reference as prod_ref,
            orddet.product_name,
            COALESCE(img.id_image, 0) as id_image
        ')
        ->from('orders', 'ord')
        ->innerJoin('customer', 'cust', 'ord.id_customer = cust.id_customer')
        ->innerJoin('order_detail', 'orddet', 'ord.id_order = orddet.id_order')
        ->innerJoin('product', 'prod', 'orddet.product_id = prod.id_product')
        ->leftJoin('image', 'img', 'prod.id_product = img.id_product AND img.cover = 1')
        ->where('ord.id_order = ' . (int)$orderId)
        ->groupBy('prod.id_product');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Check current order status with custplace records
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderStatusWithCustplace(int $orderId): array
    {
        $query = new DbQuery();
        $query->select('DISTINCT cust.status_order, ord.current_state')
            ->from(CustplaceConstants::TABLE_CUSTPLACE, 'cust')
            ->innerJoin('orders', 'ord', 'ord.id_order = cust.id_order')
            ->where('cust.status_order = \'' . pSQL(CustplaceConstants::STATUS_SUCCESS) . '\'')
            ->where('ord.id_order = ' . (int)$orderId);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get current order state
     *
     * @param int $orderId
     * @return array
     */
    public function getCurrentOrderState(int $orderId): array
    {
        $query = new DbQuery();
        $query->select('current_state')
            ->from('orders')
            ->where('id_order = ' . (int)$orderId);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Create custplace table
     *
     * @return bool
     */
    public function createTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . CustplaceConstants::TABLE_CUSTPLACE . '`(
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_order` INT(10) unsigned NOT NULL,
            `dateRequest` DATETIME NOT NULL,
            `status_order` VARCHAR(255) NOT NULL,
            `invitation_id` VARCHAR(50),
            PRIMARY KEY (id),
            INDEX idx_order_id (id_order)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Drop custplace table
     *
     * @return bool
     */
    public function dropTable(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . CustplaceConstants::TABLE_CUSTPLACE . '`';
        return Db::getInstance()->execute($sql);
    }
}