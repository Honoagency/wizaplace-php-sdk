<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types = 1);

namespace Wizaplace\SDK\Tests\Catalog;

use GuzzleHttp\Psr7\Response;
use Wizaplace\SDK\Catalog\AttributeType;
use Wizaplace\SDK\Catalog\AttributeVariant;
use Wizaplace\SDK\Catalog\CatalogService;
use Wizaplace\SDK\Catalog\Declination;
use Wizaplace\SDK\Catalog\Option;
use Wizaplace\SDK\Catalog\ProductAttachment;
use Wizaplace\SDK\Catalog\ProductAttribute;
use Wizaplace\SDK\Catalog\ProductLocation;
use Wizaplace\SDK\Catalog\ProductReport;
use Wizaplace\SDK\Catalog\ProductVideo;
use Wizaplace\SDK\Exception\NotFound;
use Wizaplace\SDK\Exception\SomeParametersAreInvalid;
use Wizaplace\SDK\Tests\ApiTestCase;

/**
 * @see CatalogService
 */
final class CatalogServiceTest extends ApiTestCase
{
    public function testGetProductById()
    {
        $catalogService = $this->buildCatalogService();

        $product = $catalogService->getProductById(1);

        $this->assertSame('1', $product->getId());
        $this->assertSame('test-product-slug', $product->getSlug());
        $this->assertSame('Z11 Plus Boîtier PC en Acier ATX', $product->getName());
        $this->assertSame('', $product->getDescription());
        $this->assertCount(1, $product->getDeclinations());
        $this->assertCount(0, $product->getAttributes());
        $this->assertCount(1, $product->getCategoryPath());
        $this->assertSame(['informatique'], $product->getCategorySlugs());
        $this->assertSame('978020137962', $product->getCode());
        $this->assertGreaterThan(1500000000, $product->getCreationDate()->getTimestamp());
        $this->assertSame(67.9, $product->getMinPrice());
        $this->assertCount(3, $product->getShippings());
        $this->assertSame('', $product->getShortDescription());
        $this->assertSame('INFO-001', $product->getSupplierReference());
        $this->assertTrue($product->isTransactional());
        $this->assertSame(0.0, $product->getGreenTax());
        $this->assertSame(1.23, $product->getWeight());
        $this->assertNull($product->getAverageRating());
        $this->assertNull($product->getGeolocation());
        $this->assertNull($product->getVideo());
        $this->assertCount(0, $product->getAttachments());

        $companies = $product->getCompanies();
        $this->assertCount(1, $companies);
        $this->assertSame(3, $companies[0]->getId());
        $this->assertSame('The World Company Inc.', $companies[0]->getName());
        $this->assertSame('the-world-company-inc.', $companies[0]->getSlug());
        $this->assertNull($companies[0]->getAverageRating());
        $this->assertNull($companies[0]->getImage());
        $this->assertTrue($companies[0]->isProfessional());
    }

    public function testGetProductWithComplexAttributes()
    {
        $catalogService = $this->buildCatalogService();

        $product = $catalogService->getProductById(5);

        $this->assertSame('5', $product->getId());
        $this->assertSame('product-with-complex-attributes', $product->getSlug());

        $expectedAttributes = [
            new ProductAttribute([
                'id' => 3,
                'name' => 'Commentaire',
                'value' => 'Commentaire #12M%M_°09£*/.?',
                'valueIds' => [],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 1,
                'name' => 'Couleur',
                'value' => [
                    'Blanc',
                    'Rouge',
                ],
                'valueIds' => [2, 3],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 4,
                'name' => 'Groupe attributs',
                'value' => null,
                'valueIds' => [],
                'children' => [
                    [
                        'id' => 7,
                        'name' => 'Complément adresse',
                        'value' => 'Complément d\'adresse pour la stepo',
                        'valueIds' => [],
                        'children' => [],
                        'imageUrls' => [],
                    ],
                    [
                        'id' => 5,
                        'name' => 'Couleur secondaire',
                        'value' => ['Orange'],
                        'valueIds' => [9],
                        'children' => [],
                        'imageUrls' => [],
                    ],
                    [
                        'id' => 6,
                        'name' => 'Pointure',
                        'value' => ['44'],
                        'valueIds' => [17],
                        'children' => [],
                        'imageUrls' => [],
                    ],
                ],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 2,
                'name' => 'Taille',
                'value' => ['M'],
                'valueIds' => [5],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 0,
                'name' => 'Free attribute multiple',
                'value' => [
                    'réponse - 1 #',
                    'réponse - 2 @',
                    4985,
                ],
                'valueIds' => [],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 0,
                'name' => 'Free attribute simple',
                'value' => ['valeur simple du free attribute #12M%M_°09£*/.?'],
                'valueIds' => [],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 0,
                'name' => 'Free attribute simple mais en tableau',
                'value' => ['une bien belle valeur déjà encapsulée'],
                'valueIds' => [],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 0,
                'name' => 'Free attribute integer ?',
                'value' => [92254094],
                'valueIds' => [],
                'children' => [],
                'imageUrls' => [],
            ]),
            new ProductAttribute([
                'id' => 0,
                'name' => 'Free attribute integer mais en tableau',
                'value' => ['la même histoire par ici'],
                'valueIds' => [],
                'children' => [],
                'imageUrls' => [],
            ]),
        ];

        $this->assertEquals($expectedAttributes, $product->getAttributes());
    }

    public function testGetNonExistingProductById()
    {
        $catalogService = $this->buildCatalogService();

        $this->expectException(NotFound::class);
        $catalogService->getProductById(404);
    }

    public function testSearchOneProductByName()
    {
        $catalogService = $this->buildCatalogService();

        $result = $catalogService->search('Product');

        $products = $result->getProducts();
        $this->assertCount(3, $products);

        $product = $products[0];
        $this->assertSame('4', $product->getId());
        $this->assertSame('product-with-shippings', $product->getSlug());
        $this->assertTrue($product->isAvailable());
        $this->assertGreaterThan(1400000000, $product->getCreatedAt()->getTimestamp());
        $this->assertNull($product->getAverageRating());
        $this->assertSame('Product with shippings', $product->getName());
        $this->assertSame(['N'], $product->getCondition());
        $this->assertSame(1, $product->getDeclinationCount());
        $this->assertSame('', $product->getSubtitle());
        $this->assertSame("La nouvelle génération de notre tablette Fire phare - désormais plus fine, plus légère, dotée d'une plus longue autonomie et d'un écran amélioré.", $product->getShortDescription());
        $this->assertGreaterThanOrEqual($product->getCreatedAt()->getTimestamp(), $product->getUpdatedAt()->getTimestamp());
        $this->assertNull($product->getAffiliateLink());
        $this->assertSame(['special-category-dedicated-to-specific-tests'], $product->getCategorySlugs());
        $this->assertCount(1, $product->getCategoryPath());
        $this->assertNull($product->getMainImage());
        $this->assertSame(9.9, $product->getMinimumPrice());
        $this->assertNull($product->getCrossedOutPrice());
        $this->assertCount(0, $product->getAttributes());
        $this->assertSame(45.75845, $product->getGeolocation()->getLatitude());
        $this->assertSame(4.799044, $product->getGeolocation()->getLongitude());
        $this->assertSame('Lyon 5e', $product->getGeolocation()->getLabel());
        $this->assertSame('69005', $product->getGeolocation()->getZipcode());
        $companies = $product->getCompanies();
        $this->assertCount(1, $companies);
        $this->assertSame('The World Company Inc.', $companies[0]->getName());
        $this->assertSame('the-world-company-inc.', $companies[0]->getSlug());
        $this->assertSame(3, $companies[0]->getId());
        $this->assertTrue($companies[0]->isProfessional());
        $this->assertNull($companies[0]->getImage());
        $this->assertNull($companies[0]->getAverageRating());


        $pagination = $result->getPagination();
        $this->assertSame(1, $pagination->getNbPages());
        $this->assertSame(3, $pagination->getNbResults());
        $this->assertSame(1, $pagination->getPage());
        $this->assertSame(12, $pagination->getResultsPerPage());

        $facets = $result->getFacets();
        $this->assertCount(9, $facets);
        $this->assertSame('categories', $facets[0]->getName());
        $this->assertSame('Catégorie', $facets[0]->getLabel());
        $this->assertSame([
            5 => [
            'label' => 'Special category dedicated to specific tests',
            'count' => '3',
            'position' => '0',
            ],
        ], $facets[0]->getValues());
        $this->assertFalse($facets[0]->isIsNumeric());
    }

    public function testGetCompanyById()
    {
        $catalogService = $this->buildCatalogService();

        $company = $catalogService->getCompanyById(3);

        $this->assertSame(3, $company->getId());
        $this->assertSame('The World Company Inc.', $company->getName());
        $this->assertSame('the-world-company-inc.', $company->getSlug());
        $this->assertSame('The World Company Inc.', $company->getDescription());
        $this->assertSame('40 rue Laure Diebold', $company->getAddress());
        $this->assertSame('01 02 03 04 05', $company->getPhoneNumber());
        $this->assertTrue($company->isProfessional());
        $this->assertEquals(45.778847, $company->getLocation()->getLatitude());
        $this->assertEquals(4.800039, $company->getLocation()->getLongitude());
        $this->assertSame(5, $company->getAverageRating());
        $this->assertSame('Lorem Ipsum', $company->getTerms());

        $company = $catalogService->getCompanyById(4);

        $this->assertSame(4, $company->getId());
        $this->assertSame('C2C company', $company->getName());
        $this->assertSame('c2c-company', $company->getSlug());
        $this->assertSame('C2C company', $company->getDescription());
        $this->assertSame('40 rue Laure Diebold', $company->getAddress());
        $this->assertSame('01 02 03 04 05', $company->getPhoneNumber());
        $this->assertFalse($company->isProfessional());
        $this->assertNull($company->getLocation());
        $this->assertNull($company->getAverageRating());
        $this->assertSame('Lorem Ipsum', $company->getTerms());
    }

    public function testGetC2cCompanyById()
    {
        $catalogService = $this->buildCatalogService();

        $company = $catalogService->getCompanyById(4);

        $this->assertSame(4, $company->getId());
        $this->assertSame('C2C company', $company->getName());
        $this->assertSame('c2c-company', $company->getSlug());
        $this->assertSame('C2C company', $company->getDescription());
        $this->assertSame('40 rue Laure Diebold', $company->getAddress());
        $this->assertSame('01 02 03 04 05', $company->getPhoneNumber());
        $this->assertFalse($company->isProfessional());
        $this->assertNull($company->getLocation());
        $this->assertNull($company->getAverageRating());
        $this->assertNull($company->getImage());
        $this->assertSame('Lorem Ipsum', $company->getTerms());
    }

    public function testGetCategory()
    {
        $category = $this->buildCatalogService()->getCategory(2);
        $this->assertSame(2, $category->getId());
        $this->assertNull($category->getParentId());
        $this->assertSame('Catégorie principale', $category->getName());
        $this->assertSame('categorie-principale', $category->getSlug());
        $this->assertSame('', $category->getDescription());
        $this->assertSame(10, $category->getPosition());
        $this->assertSame(0, $category->getProductCount());
        $this->assertNull($category->getImage());
    }

    public function testGetCategoryTree()
    {
        $categoryTree = $this->buildCatalogService()->getCategoryTree();
        $this->assertCount(3, $categoryTree);

        $category = $categoryTree[2]->getCategory();

        $this->assertSame(2, $category->getId());
        $this->assertSame('Catégorie principale', $category->getName());
        $this->assertSame('categorie-principale', $category->getSlug());
        $this->assertSame('', $category->getDescription());
        $this->assertSame(10, $category->getPosition());
        $this->assertSame(0, $category->getProductCount());

        $childrenTrees = $categoryTree[1]->getChildren();
        $this->assertCount(1, $childrenTrees);
        $childCategory = $childrenTrees[0]->getCategory();
        $this->assertSame(4, $childCategory->getId());
        $this->assertSame('Écrans', $childCategory->getName());
        $this->assertSame('ecrans', $childCategory->getSlug());
        $this->assertSame('', $childCategory->getDescription());
        $this->assertSame(0, $childCategory->getPosition());
        $this->assertSame(1, $childCategory->getProductCount());
    }

    public function testGetAttributes()
    {
        $attributes = $this->buildCatalogService()->getAttributes();

        $this->assertCount(9, $attributes);
        foreach ($attributes as $attribute) {
            $this->assertGreaterThan(0, $attribute->getId());
            $this->assertGreaterThanOrEqual(0, $attribute->getPosition());
            $this->assertNotEmpty($attribute->getName());
            $this->assertNotEmpty($attribute->getType()->getValue());
        }
    }

    public function testGetAttribute()
    {
        $attribute = $this->buildCatalogService()->getAttribute(1);

        $this->assertSame(1, $attribute->getId());
        $this->assertSame(0, $attribute->getPosition());
        $this->assertSame('Couleur', $attribute->getName());
        $this->assertTrue(AttributeType::CHECKBOX_MULTIPLE()->equals($attribute->getType()));
        $this->assertNull($attribute->getParentId());
    }

    public function testGetNonExistingAttribute()
    {
        $this->expectException(NotFound::class);
        $this->buildCatalogService()->getAttribute(404);
    }

    public function testGetAttributeVariant()
    {
        $variant = $this->buildCatalogService()->getAttributeVariant(3);

        $this->assertSame(3, $variant->getId());
        $this->assertSame(1, $variant->getAttributeId());
        $this->assertSame('Rouge', $variant->getName());
        $this->assertSame('Le rouge est un champ chromatique regroupant les couleurs vives situées sur le cercle chromatique entre l\'orange et les pourpres.', $variant->getDescription());
        $this->assertSame('rouge', $variant->getSlug());
        $this->assertNull($variant->getImage());
    }

    public function testGetNonExistingAttributeVariant()
    {
        $this->expectException(NotFound::class);
        $this->buildCatalogService()->getAttributeVariant(404);
    }

    public function testGetAttributeVariants()
    {
        $variants = $this->buildCatalogService()->getAttributeVariants(1);

        $expectedVariants = [
            new AttributeVariant([
                'id' => 1,
                'attributeId' => 1,
                'name' => 'Bleu',
                'slug' => '',
                'image' => null,
                'description' => '',
            ]),
            new AttributeVariant([
                'id' => 2,
                'attributeId' => 1,
                'name' => 'Blanc',
                'slug' => 'blanc',
                'image' => null,
                'description' => '',
            ]),
            new AttributeVariant([
                'id' => 3,
                'attributeId' => 1,
                'name' => 'Rouge',
                'slug' => 'rouge',
                'image' => null,
                'description' => 'Le rouge est un champ chromatique regroupant les couleurs vives situées sur le cercle chromatique entre l\'orange et les pourpres.',
            ]),
        ];

        $this->assertEquals($expectedVariants, $variants);
    }

    public function testGetNonExistingAttributeVariants()
    {
        $this->expectException(NotFound::class);
        $this->buildCatalogService()->getAttributeVariants(404);
    }

    public function testGetProductWithOptions()
    {
        $catalogService = $this->buildCatalogService();
        $product = $catalogService->getProductById(3);

        $expectedDeclinations = [
            new Declination([
                'id' => '3_8_7',
                'code' => 'size_13',
                'supplierReference' => 'INFO-ECRAN-001',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => true,
                'options' => [
                    [
                        'id' => 8,
                        'name' => 'size',
                        'variantId' => 7,
                        'variantName' => '13',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '3_8_8',
                'code' => 'size_15',
                'supplierReference' => 'INFO-ECRAN-001',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => true,
                'options' => [
                    [
                        'id' => 8,
                        'name' => 'size',
                        'variantId' => 8,
                        'variantName' => '15',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '3_8_9',
                'code' => 'size_17',
                'supplierReference' => 'INFO-ECRAN-001',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => true,
                'options' => [
                    [
                        'id' => 8,
                        'name' => 'size',
                        'variantId' => 9,
                        'variantName' => '17',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '3_8_10',
                'code' => 'size_21',
                'supplierReference' => 'INFO-ECRAN-001',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => true,
                'options' => [
                    [
                        'id' => 8,
                        'name' => 'size',
                        'variantId' => 10,
                        'variantName' => '21',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
        ];

        $expectedOption = [
            'id' => 8,
            'name' => 'size',
            'variants' => [
                [
                    'id' => 7,
                    'name' => '13',
                ],
                [
                    'id' => 8,
                    'name' => '15',
                ],
                [
                    'id' => 9,
                    'name' => '17',
                ],
                [
                    'id' => 10,
                    'name' => '21',
                ],
            ],
        ];

        $expectedOptions = [
            new Option($expectedOption),
        ];

        $this->assertSame('3', $product->getId());
        $this->assertEquals($expectedDeclinations, $product->getDeclinations());
        $this->assertEquals($expectedOptions, $product->getOptions());
        $this->assertTrue(in_array($product->getDeclinationFromOptions([7]), $expectedDeclinations));
        $this->assertEquals($expectedDeclinations[0], $product->getDeclinationFromOptions([7]));
    }

    public function testGetProductWithMultipleOptions()
    {
        $catalogService = $this->buildCatalogService();
        $product = $catalogService->getProductById(2);

        $expectedDeclinations = [
            new Declination([
                'id' => '2_6_1',
                'code' => 'color_white',
                'supplierReference' => 'INFO-002',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 1,
                        'variantName' => 'white',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_1_7_5',
                'code' => '90204479D2',
                'supplierReference' => 'INFO-002',
                'price' => 0,
                'originalPrice' => 0,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 0,
                    'priceWithoutVat' => 0,
                    'vat' => 0,
                ],
                'greenTax' => 0,
                'amount' => 0,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 1,
                        'variantName' => 'white',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 5,
                        'variantName' => 'wireless',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_1_7_6',
                'code' => '90204479D2',
                'supplierReference' => 'INFO-002',
                'price' => 0,
                'originalPrice' => 0,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 0,
                    'priceWithoutVat' => 0,
                    'vat' => 0,
                ],
                'greenTax' => 0,
                'amount' => 0,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 1,
                        'variantName' => 'white',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 6,
                        'variantName' => 'wired',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_2',
                'code' => 'color_black',
                'supplierReference' => 'INFO-002',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 2,
                        'variantName' => 'black',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_2_7_5',
                'code' => '90204479D2',
                'supplierReference' => 'INFO-002',
                'price' => 0,
                'originalPrice' => 0,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 0,
                    'priceWithoutVat' => 0,
                    'vat' => 0,
                ],
                'greenTax' => 0,
                'amount' => 0,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 2,
                        'variantName' => 'black',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 5,
                        'variantName' => 'wireless',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_2_7_6',
                'code' => '90204479D2',
                'supplierReference' => 'INFO-002',
                'price' => 0,
                'originalPrice' => 0,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 0,
                    'priceWithoutVat' => 0,
                    'vat' => 0,
                ],
                'greenTax' => 0,
                'amount' => 0,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 2,
                        'variantName' => 'black',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 6,
                        'variantName' => 'wired',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_3',
                'code' => 'color_blue',
                'supplierReference' => 'INFO-002',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 3,
                        'variantName' => 'blue',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_3_7_5',
                'code' => '90204479D2',
                'supplierReference' => 'INFO-002',
                'price' => 0,
                'originalPrice' => 0,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 0,
                    'priceWithoutVat' => 0,
                    'vat' => 0,
                ],
                'greenTax' => 0,
                'amount' => 0,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 3,
                        'variantName' => 'blue',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 5,
                        'variantName' => 'wireless',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_3_7_6',
                'code' => '90204479D2',
                'supplierReference' => 'INFO-002',
                'price' => 0,
                'originalPrice' => 0,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 0,
                    'priceWithoutVat' => 0,
                    'vat' => 0,
                ],
                'greenTax' => 0,
                'amount' => 0,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 3,
                        'variantName' => 'blue',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 6,
                        'variantName' => 'wired',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_4',
                'code' => 'color_red',
                'supplierReference' => 'INFO-002',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 4,
                        'variantName' => 'red',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_4_7_5',
                'code' => 'color_red_connectivity_wireless',
                'supplierReference' => 'INFO-002',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 10,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 4,
                        'variantName' => 'red',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 5,
                        'variantName' => 'wireless',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
            new Declination([
                'id' => '2_6_4_7_6',
                'code' => 'color_red_connectivity_wired',
                'supplierReference' => 'INFO-002',
                'price' => 15.5,
                'originalPrice' => 15.5,
                'crossedOutPrice' => null,
                'prices' => [
                    'priceWithTaxes' => 15.5,
                    'priceWithoutVat' => 15.18,
                    'vat' => 0.32,
                ],
                'greenTax' => 0,
                'amount' => 8,
                'affiliateLink' => null,
                'images' => [],
                'isBrandNew' => false,
                'options' => [
                    [
                        'id' => 6,
                        'name' => 'color',
                        'variantId' => 4,
                        'variantName' => 'red',
                    ],
                    [
                        'id' => 7,
                        'name' => 'connectivity',
                        'variantId' => 6,
                        'variantName' => 'wired',
                    ],
                ],
                'company' => [
                    'id' => 3,
                    'name' => 'The World Company Inc.',
                    'slug' => 'the-world-company-inc.',
                    'isProfessional' => true,
                    'image' => null,
                    'averageRating' => null,
                ],
            ]),
        ];

        $this->assertSame('2', $product->getId());

        $this->assertEquals($expectedDeclinations[0], $product->getDeclinationFromOptions([1]));
        $this->assertEquals($expectedDeclinations[1], $product->getDeclinationFromOptions([1, 5]));
        $this->assertEquals($expectedDeclinations[2], $product->getDeclinationFromOptions([1, 6]));
        $this->assertEquals($expectedDeclinations[3], $product->getDeclinationFromOptions([2]));
        $this->assertEquals($expectedDeclinations[4], $product->getDeclinationFromOptions([2, 5]));
        $this->assertEquals($expectedDeclinations[5], $product->getDeclinationFromOptions([2, 6]));
        $this->assertEquals($expectedDeclinations[6], $product->getDeclinationFromOptions([3]));
        $this->assertEquals($expectedDeclinations[7], $product->getDeclinationFromOptions([3, 5]));
        $this->assertEquals($expectedDeclinations[8], $product->getDeclinationFromOptions([3, 6]));
        $this->assertEquals($expectedDeclinations[9], $product->getDeclinationFromOptions([4]));
        $this->assertEquals($expectedDeclinations[10], $product->getDeclinationFromOptions([4, 5]));
        $this->assertEquals($expectedDeclinations[11], $product->getDeclinationFromOptions([4, 6]));
    }

    public function testGetProductWithGeolocation()
    {
        $location = $this->buildCatalogService()->getProductById(6)->getGeolocation();
        $this->assertInstanceOf(ProductLocation::class, $location);
        $this->assertSame(45.778848, $location->getLatitude());
        $this->assertSame(4.800039, $location->getLongitude());
        $this->assertSame('Wizacha', $location->getLabel());
        $this->assertSame('69009', $location->getZipcode());
    }

    public function testGetProductWithAttachments()
    {
        $attachments = $this->buildCatalogService()->getProductById(7)->getAttachments();

        $this->assertCount(1, $attachments);
        $this->assertContainsOnly(ProductAttachment::class, $attachments);

        $attachment = $attachments[0];

        $this->assertNotEmpty($attachment->getId());
        $this->assertSame('Manuel de montage', $attachment->getLabel());

        $response = $this->buildApiClient()->rawRequest('GET', $attachment->getUrl());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('attachment; filename="', $response->getHeaderLine('Content-Disposition'));
    }

    public function testGetProductWithVideo()
    {
        $video = $this->buildCatalogService()->getProductById(3)->getVideo();
        $this->assertInstanceOf(ProductVideo::class, $video);
        $this->assertSame('//s3-eu-west-1.amazonaws.com/wizachatest/videos/414375b2-61cb-4260-b82b-4a2636cb5673/480-00001.png', $video->getThumbnailUrl());
        $this->assertSame('//s3-eu-west-1.amazonaws.com/wizachatest/videos/414375b2-61cb-4260-b82b-4a2636cb5673/480.mp4', $video->getVideoUrl());
    }

    public function testReportingProduct()
    {
        $report = (new ProductReport())
            ->setProductId('1')
            ->setReporterEmail('user@wizaplace.com')
            ->setReporterName('Mr. User')
            ->setMessage('I am shocked!');

        $this->buildCatalogService()->reportProduct($report);
        // We don't have a way to check that the report was saved.
        // So we just check that a request was made successfully
        $this->assertCount(1, static::$historyContainer);
        /** @var Response $response */
        $response = static::$historyContainer[0]['response'];
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testReportingNonExistingProduct()
    {
        $report = (new ProductReport())
            ->setProductId('404')
            ->setReporterEmail('user@wizaplace.com')
            ->setReporterName('User')
            ->setMessage('Should get a 404');

        $this->expectException(NotFound::class);
        $this->buildCatalogService()->reportProduct($report);
    }

    public function testReportingProductWithInvalidEmail()
    {
        $report = (new ProductReport())
            ->setProductId('1')
            ->setReporterEmail('user@@wizaplace.com')
            ->setReporterName('User')
            ->setMessage('Should get a 400');

        $this->expectException(SomeParametersAreInvalid::class);
        $this->expectExceptionCode(400);
        $this->buildCatalogService()->reportProduct($report);
    }

    public function testReportingProductWithMissingField()
    {
        $report = (new ProductReport())
            ->setProductId('1')
            ->setReporterEmail('user@wizaplace.com')
            ->setReporterName('User');

        $this->expectException(SomeParametersAreInvalid::class);
        $this->buildCatalogService()->reportProduct($report);
    }

    private function buildCatalogService(): CatalogService
    {
        return new CatalogService($this->buildApiClient());
    }
}
