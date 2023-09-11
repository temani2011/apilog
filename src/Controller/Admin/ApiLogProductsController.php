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
use EO\ApiLog\Grid\Definition\Factory\ApiLogProductsGridDefinitionFactory;
use EO\ApiLog\Grid\Filters\ApiLogProductsFilters;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Service\Grid\ResponseBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShop\PrestaShop\Core\Exception\DatabaseException;

class ApiLogProductsController extends FrameworkBundleAdminController
{
    public $tabName = 'Новые товары';

    /**
     * List api_log
     *
     * @param ApiLogProductsFilters $filters
     *
     * @return Response
     */
    public function indexAction(ApiLogProductsFilters $filters): Response
    {
        $gridFactory = $this->get('api_log.grid.factory.products');
        $grid = $gridFactory->getGrid($filters);

        return $this->render('@Modules/eo_api_log/views/templates/admin/api_log/index.html.twig', [
            'enableSidebar'          => true,
            'layoutTitle'            => 'Новые товары',
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
            $this->get('api_log.grid.definition.factory.products'),
            $request,
            ApiLogProductsGridDefinitionFactory::GRID_ID,
            'admin_api_log_products_list'
        );
    }

    /**
     * Create product action
     *
     * @param int $id
     *
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function createAction(int $id)
    {
        $repository = $this->get('api_log.repository');
        $errors = [];

        try {
            $product = $repository->createProduct($id);
        } catch (\Throwable $th) {
            $errors[] = [
                'key' => 'Не удалось создать товар. %s',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [$th->getMessage()],
            ];
        }

        if (0 === count($errors)) {
            $this->addFlash('success', 'Успешное создание товара: ' . $product->id);
        } else {
            $this->flashErrors($errors);
        }

        return $this->redirectToRoute('admin_api_log_products_list');
    }

    /**
     * Ignore product action
     *
     * @param int $id
     *
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function ignoreAction(int $id)
    {
        $repository = $this->get('api_log.repository');
        $errors = [];

        try {
            $product = $repository->ignoreProduct($id);
        } catch (\Throwable $th) {
            $errors[] = [
                'key' => 'Не удалось добавить в архив. %s',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [$th->getMessage()],
            ];
        }

        if (0 === count($errors)) {
            $this->addFlash('success', 'Успешно добавлено.');
        } else {
            $this->flashErrors($errors);
        }

        return $this->redirectToRoute('admin_api_log_products_list');
    }

    /**
     * Export features form supplier API
     *
     * @param int $id
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function exportFeaturesAction(int $id, Request $request)
    {
        /** @var ApiLogRepository $repository */
        $repository = $this->get('api_log.repository');
        $errors = [];

        try {
            $repository->exportFeatures($id);
        } catch (\Throwable $th) {
            $errors[] = [
                'key' => 'Ошибка получения характеристик. %s',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [$th->getMessage()],
            ];
        }

        if (count($errors) > 0) {
            $this->flashErrors($errors);
            return $this->redirectToRoute('admin_api_log_products_list');
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control', 'no-store, no-cache');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="features_' . $id . '_' . date('Y-m-d') . '.csv"'
        );

        return $response;
    }

    /**
     * Ignore products on bulk action
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function bulkIgnoreAction(ApiLogProductsFilters $filters, Request $request)
    {
        $ids = $this->getBulkFromRequest($filters, $request);

        /** @var ApiLogRepository $repository */
        $repository = $this->get('api_log.repository');

        $results = $repository->ignoreProductBulk($ids);

        if (count($results['products'])) {
            $productIds = implode(',', array_map(function ($item) {
                return $item->id_log;
            }, $results['products']));
            $this->addFlash('success', 'Успешное добавление товаров в архив: ' . $productIds);
        }

        if (count($results['errors'])) {
            $message = 'Ошибки при создании товаров: <br>' . implode('<br>', $results['errors']);
            $this->flashErrors([$message]);
        }

        return $this->redirectToRoute('admin_api_log_products_list');
    }

    /**
     * Creates products on bulk action
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function bulkCreateAction(ApiLogProductsFilters $filters, Request $request)
    {
        $ids = $this->getBulkFromRequest($filters, $request);

        /** @var ApiLogRepository $repository */
        $repository = $this->get('api_log.repository');

        $results = $repository->createProductBulk($ids);

        if (count($results['products'])) {
            $productIds = implode(',', array_map(function ($item) {
                return $item->id;
            }, $results['products']));
            $this->addFlash('success', 'Успешное создание товаров: ' . $productIds);
        }

        if (count($results['errors'])) {
            $message = 'Ошибки при создании товаров: <br>' . implode('<br>', $results['errors']);
            $this->flashErrors([$message]);
        }

        return $this->redirectToRoute('admin_api_log_products_list');
    }

    /**
     * Export features form supplier API
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function bulkExportFeaturesAction(ApiLogProductsFilters $filters, Request $request)
    {
        $ids = $this->getBulkFromRequest($filters, $request);

        /** @var ApiLogRepository $repository */
        $repository = $this->get('api_log.repository');

        try {
           $repository->exportFeaturesBulk($ids);
        } catch (\Exception $e) {
            $this->addFlash('Ошибка экспорта характеристик', $e->getMessage());
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control', 'no-store, no-cache');
        $response->headers->set('Content-Disposition', 'attachment; filename="features_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getBulkFromRequest(ApiLogProductsFilters $filters, Request $request)
    {
        if ($request->query->get('select-all') === 'true') {
            $gridFactory = $this->get('api_log.grid.data.factory.products');
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
        return [];
    }
}
