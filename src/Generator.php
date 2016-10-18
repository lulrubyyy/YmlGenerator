<?php

/*
 * This file is part of the Bukashk0zzzYmlGenerator
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\YmlGenerator;

use Bukashk0zzz\YmlGenerator\Model\Category;
use Bukashk0zzz\YmlGenerator\Model\Currency;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferInterface;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferParam;
use Bukashk0zzz\YmlGenerator\Model\ShopInfo;

/**
 * Class Generator
 *
 * @author Denis Golubovskiy <bukashk0zzz@gmail.com>
 */
class Generator
{
    /**
     * @var string
     */
    private $tmpFile;

    /**
     * @var \XMLWriter
     */
    private $writer;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * Generator constructor.
     * @param Settings $settings
     */
    public function __construct($settings = null)
    {
        $this->settings = $settings instanceof Settings ? $settings: new Settings();
        $this->tmpFile = $this->settings->getOutputFile() !== null ? tempnam(sys_get_temp_dir(), 'YMLGenerator') : 'php://output';

        $this->writer = new \XMLWriter();
        $this->writer->openUri($this->tmpFile);

        if ($this->settings->getIndentString()) {
            $this->writer->setIndentString($this->settings->getIndentString());
            $this->writer->setIndent(true);
        }
    }

    /**
     * @param ShopInfo $shopInfo
     * @param array    $currencies
     * @param array    $categories
     * @param array    $offers
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function generate(ShopInfo $shopInfo, array $currencies, array $categories, array $offers)
    {
        try {
            $this->addHeader();

            $this->addShopInfo($shopInfo);
            $this->addCurrencies($currencies);
            $this->addCategories($categories);
            $this->addOffers($offers);

            $this->addFooter();

            if (null !== $this->settings->getOutputFile()) {
                rename($this->tmpFile, $this->settings->getOutputFile());
            }

            return true;
        } catch (\Exception $exception) {
            throw new \RuntimeException('Problem with generating YML file: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Add document header
     */
    protected function addHeader()
    {
        $this->writer->startDocument('1.0', $this->settings->getEncoding());
        $this->writer->startElement('yml_catalog');
        $this->writer->writeAttribute('date', date('Y-m-d H:i'));
        $this->writer->startElement('shop');
    }

    /**
     * Add document footer
     */
    protected function addFooter()
    {
        $this->writer->fullEndElement();
        $this->writer->fullEndElement();
        $this->writer->endDocument();
    }

    /**
     * Adds shop element data. (See https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#shop)
     * @param ShopInfo $shopInfo
     */
    protected function addShopInfo(ShopInfo $shopInfo)
    {
        foreach ($shopInfo->toArray() as $name => $value) {
            if ($value !== null) {
                $this->writer->writeElement($name, $value);
            }
        }
    }

    /**
     * @param Currency $currency
     */
    protected function addCurrency(Currency $currency)
    {
        $this->writer->startElement('currency');
        $this->writer->writeAttribute('id', $currency->getId());
        $this->writer->writeAttribute('rate', $currency->getRate());
        $this->writer->endElement();
    }

    /**
     * @param Category $category
     */
    protected function addCategory(Category $category)
    {
        $this->writer->startElement('category');
        $this->writer->writeAttribute('id', $category->getId());

        if ($category->getParentId() !== null) {
            $this->writer->writeAttribute('parentId', $category->getParentId());
        }

        $this->writer->text($category->getName());
        $this->writer->fullEndElement();
    }

    /**
     * @param OfferInterface $offer
     */
    protected function addOffer(OfferInterface $offer)
    {
        $this->writer->startElement('offer');
        $this->writer->writeAttribute('id', $offer->getId());
        $this->writer->writeAttribute('available', $offer->isAvailable() ? 'true' : 'false');

        if ($offer->getType() !== null) {
            $this->writer->writeAttribute('type', $offer->getType());
        }

        foreach ($offer->toArray() as $name => $value) {
            if ($value !== null) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $this->writer->writeElement($name, $value);
            }
        }

        /** @var OfferParam $param */
        foreach ($offer->getParams() as $param) {
            if ($param instanceof OfferParam) {
                $this->writer->startElement('param');

                $this->writer->writeAttribute('name', $param->getName());
                if ($param->getUnit()) {
                    $this->writer->writeAttribute('unit', $param->getUnit());
                }
                $this->writer->text($param->getValue());

                $this->writer->endElement();
            }
        }
        $this->writer->fullEndElement();
    }

    /**
     * Adds <currencies> element. (See https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#currencies)
     * @param array $currencies
     */
    private function addCurrencies(array $currencies)
    {
        $this->writer->startElement('currencies');

        /** @var Currency $currency */
        foreach ($currencies as $currency) {
            if ($currency instanceof Currency) {
                $this->addCurrency($currency);
            }
        }

        $this->writer->fullEndElement();
    }

    /**
     * Adds <categories> element. (See https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#categories)
     * @param array $categories
     */
    private function addCategories(array $categories)
    {
        $this->writer->startElement('categories');

        /** @var Category $category */
        foreach ($categories as $category) {
            if ($category instanceof Category) {
                $this->addCategory($category);
            }
        }

        $this->writer->fullEndElement();
    }

    /**
     * Adds <offers> element. (See https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#offers)
     * @param array $offers
     */
    private function addOffers(array $offers)
    {
        $this->writer->startElement('offers');

        /** @var OfferInterface $offer */
        foreach ($offers as $offer) {
            if ($offer instanceof OfferInterface) {
                $this->addOffer($offer);
            }
        }

        $this->writer->fullEndElement();
    }
}