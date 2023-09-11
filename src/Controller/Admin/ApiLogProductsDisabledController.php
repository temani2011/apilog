<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace EO\ApiLog\Controller\Admin;

use Context;
use Module;
use EO\ApiLog\Grid\Definition\Factory\ApiLogProductsDisabledGridDefinitionFactory;
use EO\ApiLog\Grid\Filters\ApiLogProductsDisabledFilters;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Service\Grid\ResponseBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\PrestaShop\Core\Exception\DatabaseException;

class ApiLogProductsDisabledController extends FrameworkBundleAdminController
{
    public $tabName = 'Выключенные товары';

    /**
     * List api_log
     *
     * @param ApiLogProductsDisabledFilters $filters
     *
     * @return Response
     */
    public function indexAction(ApiLogProductsDisabledFilters $filters): Response
    {
        $gridFactory = $this->get('api_log.grid.factory.products_disabled');
        $grid = $gridFactory->getGrid($filters);

        return $this->render('@Modules/eo_api_log/views/templates/admin/api_log/index.html.twig', [
            'enableSidebar'          => true,
            'layoutTitle'            => 'Выключенные товары',
            'headerTabContent'       => $this->getHeaderTabsContent(),
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'grid'                   => $this->presentGrid($grid),
        ]);
    }

    /**
     * Provides filters functionality.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request): RedirectResponse
    {
        /** @var ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('prestashop.bundle.grid.response_builder');

        return $responseBuilder->buildSearchResponse(
            $this->get('api_log.grid.definition.factory.products_disabled'),
            $request,
            ApiLogProductsDisabledGridDefinitionFactory::GRID_ID,
            'admin_api_log_products_disabled_list'
        );
    }

    /**
     * Return product action
     *
     * @param int $id
     *
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function returnAction(int $id)
    {
        $repository = $this->get('api_log.repository');
        $errors = [];

        try {
            $product = $repository->activateProduct($id);
        } catch (\Throwable $th) {
            $errors[] = [
                'key' => 'Could not create product. %s',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [$th->getMessage()],
            ];
        }

        if (0 === count($errors)) {
            $this->addFlash('success', 'Успешное востановление товара: ' . $product->id);
        } else {
            $this->flashErrors($errors);
        }

        return $this->redirectToRoute('admin_api_log_products_disabled_list');
    }

    /**
     * Creates products on bulk action
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function bulkReturnAction(ApiLogProductsDisabledFilters $filters, Request $request)
    {
        $ids = $this->getBulkFromRequest($filters, $request);

        /** @var ApiLogRepository $repository */
        $repository = $this->get('api_log.repository');

        $results = $repository->activateProducts($ids);

        if (count($results['products'])) {
            $productIds = implode(',', array_map(function ($item) {
                return $item->id;
            }, $results['products']));
            $this->addFlash('success', 'Успешное востановление товаров: ' . $productIds);
        }

        if (count($results['errors'])) {
            $message = 'Ошибки при востановлении товаров: <br>' . implode('<br>', $results['errors']);
            $this->flashErrors([$message]);
        }

        return $this->redirectToRoute('admin_api_log_products_disabled_list');
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getBulkFromRequest(ApiLogProductsDisabledFilters $filters, Request $request)
    {
        if ($request->query->get('select-all') === 'true') {
            $gridFactory = $this->get('api_log.grid.data.factory.products_disabled');
            $filters->remove('limit');
            $filters->remove('offset');
            $grid = $gridFactory->getData($filters)->getRecords();
            $gridArray = (array) $grid;
            $items = (array_column($gridArray[key($gridArray)], 'id'));
        } else {
            $items = $request->request->get('api_log_products_bulk');
        }

        if (!is_array($items)) {
            return [];
        }

        return $items;
    }

    /**
     * Gets the header tabs content.
     *
     * @return string
     */
    private function getHeaderTabsContent(): string
    {
        $smarty = Context::getContext()->smarty;

        $smarty->assign([
            'tabs' => Module::getInstanceByName('eo_api_log')->getTopTabs($this->tabName),
        ]);

        return $smarty->fetch('module:eo_api_log/views/templates/admin/header_tabs.tpl');
    }

    /**
     * Gets the header toolbar buttons.
     *
     * @return array
     */
    private function getToolbarButtons()
    {
        return [
            'copy' => [
                'href' => "javascript:var text = $('.js-grid').find('.js-bulk-action-checkbox:checked').closest('tr').find('td:nth-child(3)').map(function() {return this.innerText;}).get().join(); var temp = $('<input>'); $('body').append(temp); temp.val(text).select(); document.execCommand('copy'); temp.remove(); void(0);",
                'desc' => 'Копировать id товаров',
                "icon" => "content_copy",
            ],
        ];
    }
}
