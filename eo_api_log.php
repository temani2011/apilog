<?php

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class eo_api_log extends Module
{
    public function __construct()
    {
        $this->name = 'eo_api_log';
        $this->tab = 'administration';
        $this->version = '1.0';
        $this->displayName = 'Логирование API поставщиков';
        $this->author = 'Express Office';
        $this->description = 'Логирование данных от API поставщиков';
        $this->topTab = [];

        $container = SymfonyContainer::getInstance();
        $router = $container->get('router');

        if ($container) {
            $this->tabRepository = $container->get('prestashop.core.admin.tab.repository');
        }

        if ($this->isInstalled('eo_api_log')) {
            $this->topTabs = [
                [
                    'name' => 'Новые товары',
                    'link' => Context::getContext()->link->getAdminLink('AdminApiLogProducts'),
                ],
                [
                    'name' => 'Архив товаров',
                    'link' => Context::getContext()->link->getAdminLink('AdminApiLogProductsIgnored'),
                ],
                [
                    'name' => 'Изменения в цене',
                    'link' => Context::getContext()->link->getAdminLink('AdminApiLogProductsPrice'),
                ],
                [
                    'name' => 'Выключенные товары',
                    'link' => Context::getContext()->link->getAdminLink('AdminApiLogProductsDisabled'),
                ],
                [
                    'name' => 'Изменение в упаковках',
                    'link' => Context::getContext()->link->getAdminLink('AdminApiLogProductsPackages'),
                ],
            ];
        }

        parent::__construct();
    }

    public function install()
    {
        if (!parent::install()
            || !$this->installTable()
            || !$this->installTab()
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
            // || !$this->uninstallTable()
            || !$this->uninstallTab()
        ) {
            return false;
        }

        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminApiLogProducts';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Логирование API поставщиков';
        }
        $tab->id_parent = (int) $this->tabRepository->findOneIdByClassName('AdminAdvancedParameters');
        $tab->module = $this->name;

        return $tab->save();
    }

    public function uninstallTab()
    {
        $tabId = (int) $this->tabRepository->findOneIdByClassName('AdminApiLogProducts');
        if (!$tabId) {
            return true;
        }

        $tab = new Tab($tabId);

        return $tab->delete();
    }

    public function installTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `eo_api_log_products` (
                `id_log`             int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_product`         int(11) unsigned NOT NULL,
                `supplier`           varchar(255) NOT NULL,
                `supplier_code`      varchar(255) NOT NULL,
                `name`               varchar(255) NOT NULL,
                `reference`          varchar(255) NOT NULL,
                `color`              varchar(255) NOT NULL,
                `base_price`         float(20,2) NOT NULL,
                `recommend_price`    float(20,2) NOT NULL,
                `multiplicity`       int(11) unsigned NOT NULL,
                `min_pack`           int(11) unsigned NOT NULL,
                `date_add`           datetime NOT NULL,
                PRIMARY KEY          (`id_log`),
                INDEX `date_add`     (`date_add`),
                INDEX `supplier`     (`supplier`),
                INDEX `reference`    (`reference`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            CREATE TABLE IF NOT EXISTS `eo_api_log_products_ignored` (
                `id_log`             int(11) unsigned NOT NULL AUTO_INCREMENT,
                `supplier`           varchar(255) NOT NULL,
                `supplier_code`      varchar(255) NOT NULL,
                `name`               varchar(255) NOT NULL,
                PRIMARY KEY          (`id_log`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            CREATE TABLE IF NOT EXISTS `eo_api_log_products_price` (
                `id_log`             int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_product`         int(11) unsigned NOT NULL,
                `base_price`         float(20,2) NOT NULL,
                `recommend_price`    float(20,2) NOT NULL,
                `date_add`           datetime NOT NULL,
                PRIMARY KEY          (`id_log`),
                INDEX `id_product`   (`id_product`),
                INDEX `date_add`     (`date_add`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            CREATE TABLE IF NOT EXISTS `eo_api_log_products_disabled` (
                `id_log`             int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_product`         int(11) unsigned NOT NULL,
                `base_price`         float(20,2) NOT NULL,
                `recommend_price`    float(20,2) NOT NULL,
                `date_add`           datetime NOT NULL,
                `status`             ENUM("0", "1", "2") NOT NULL,
                PRIMARY KEY          (`id_log`),
                INDEX `id_product`   (`id_product`),
                INDEX `status`       (`status`),
                INDEX `date_add`     (`date_add`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            CREATE TABLE IF NOT EXISTS `eo_api_log_products_packages` (
                `id_log`             int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_product`         int(11) unsigned NOT NULL,
                `id_package`         int(11) unsigned NOT NULL,
                `ean`                varchar(255) NOT NULL,
                `qty`                int(11) unsigned NOT NULL,
                `date_add`           datetime NOT NULL,
                PRIMARY KEY          (`id_log`),
                INDEX `id_product`   (`id_product`),
                INDEX `id_package`   (`id_package`),
                INDEX `ean`          (`ean`),
                INDEX `date_add`     (`date_add`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
        );
    }

    public function uninstallTable()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `eo_api_log_products`;
            DROP TABLE IF EXISTS `eo_api_log_products_ignored`;
            DROP TABLE IF EXISTS `eo_api_log_products_price`;
            DROP TABLE IF EXISTS `eo_api_log_products_disabled`;
            DROP TABLE IF EXISTS `eo_api_log_products_packages`;'
        );
    }

    public function getTopTabs($name)
    {
        foreach ($this->topTabs as $key => $tab) {
            $this->topTabs[$key]['active'] = $tab['name'] === $name ? true : false;
        }

        return $this->topTabs;
    }
}
