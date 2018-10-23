<?php

namespace AppBundle\Form\Type\Product;

use AppBundle\Entity\Customer\Vendor;
use AppBundle\Entity\Customer\Warehouse;
use AppBundle\Entity\Product\AlcoholicCategory;
use AppBundle\Entity\Product\PackageMaterial;
use AppBundle\Entity\Product\Product;
use AppBundle\Entity\Product\ProductAttributeValue;
use AppBundle\Entity\Product\ProductCategory;
use AppBundle\Entity\Product\ProductPrice;
use AppBundle\Service\CnCodeService;
use AppBundle\Service\CustomsService;
use AppBundle\Service\ExciseService;
use AppBundle\Service\FormsCalculator;
use AppBundle\Service\OffersService;
use AppBundle\Service\PackageMaterialService;
use AppBundle\Service\ProductPriceService;
use AppBundle\Service\TaricCodeService;
use AppBundle\Service\VatRateService;
use AppBundle\Validator\TaricCode;
use AppBundle\Validator\VendorWarehouse;
use Doctrine\ORM\EntityManager;
use *****\ProductBundle\Service\ProductAttributeValueService;
use *****\ProductBundle\Service\ProductPictureService;
use *****\ProductBundle\Service\ProductService;
use *****\NotificationBundle\Dispatching\NotificationCenter;
use *****\ProductRequirementsBundle\Entity\CategoryRequirements;
use *****\ProductRequirementsBundle\Entity\ProductField;
use *****\ProductRequirementsBundle\Service\FieldService;
use *****\ProductRequirementsBundle\Service\RequirementsService;
use *****\SecurityBundle\Entity\User;
use *****\SecurityBundle\Service\UserService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @author    Voytovich Oleg <swintuskbd@gmail.com>
 * @copyright 2017
 */
class ProductType extends AbstractType
{
    /**
     * @var array
     */
    private $fields;

    /**
     * @var ProductService
     */
    private $productService;

    /**
     * @var ProductAttributeValueService
     */
    private $productAttributeValueService;

    /**
     * @var ProductPictureService
     */
    private $productPictureService;

    /**
     * @var CategoryRequirements[]
     */
    private $requirements;

    /**
     * @var Vendor
     */
    private $vendor;

    /**
     * @var OffersService
     */
    private $offersService;

    /**
     * @var VatRateService
     */
    private $vatRateService;

    /**
     * @var ProductPriceService
     */
    private $productPriceService;

    /**
     * @var CustomsService
     */
    private $customsService;

    /**
     * @var ExciseService
     */
    private $exciseService;

    /**
     * @var CnCodeService
     */
    private $cnCodeService;

    /**
     * @var TaricCodeService
     */
    private $taricCodeService;

    /**
     * @var PackageMaterialService
     */
    private $packageMaterialService;

    /**
     * @var NotificationCenter
     */
    private $notificationCenter;

    /**
     * @var User
     */
    private $adminUser;

    /**
     * @var bool
     */
    private $isTenderOffer;

    /**
     * @var RequirementsService
     */
    private $requirementsService;

    /**
     * @var FieldService
     */
    private $fieldService;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FormsCalculator
     */
    private $formsCalculator;

    /**
     * ProductType constructor.
     *
     * @param FieldService                 $fieldService
     * @param ProductService               $productService
     * @param ProductAttributeValueService $productAttributeValueService
     * @param ProductPictureService        $productPictureService
     * @param RequirementsService          $requirementsService
     * @param OffersService                $offersService,
     * @param VatRateService               $vatRateService
     * @param ProductPriceService          $productPriceService
     * @param CustomsService               $customsService
     * @param ExciseService                $exciseService
     * @param CnCodeService                $cnCodeService
     * @param TaricCodeService             $taricCodeService
     * @param NotificationCenter           $notificationCenter
     * @param UserService                  $userService
     * @param PackageMaterialService       $packageMaterialService
     * @param                              $adminUserId
     * @param EntityManager                $em
     * @param FormsCalculator              $formsCalculator
     */
    public function __construct(
        FieldService $fieldService,
        ProductService $productService,
        ProductAttributeValueService $productAttributeValueService,
        ProductPictureService $productPictureService,
        RequirementsService $requirementsService,
        OffersService $offersService,
        VatRateService $vatRateService,
        ProductPriceService $productPriceService,
        CustomsService $customsService,
        ExciseService $exciseService,
        CnCodeService $cnCodeService,
        TaricCodeService $taricCodeService,
        NotificationCenter $notificationCenter,
        UserService $userService,
        PackageMaterialService $packageMaterialService,
        $adminUserId,
        EntityManager $em,
        FormsCalculator $formsCalculator
    ) {
        $this->fieldService = $fieldService;
        $this->productService = $productService;
        $this->productAttributeValueService = $productAttributeValueService;
        $this->productPictureService = $productPictureService;
        $this->requirementsService = $requirementsService;
        $this->offersService = $offersService;
        $this->vatRateService = $vatRateService;
        $this->productPriceService = $productPriceService;
        $this->customsService = $customsService;
        $this->exciseService = $exciseService;
        $this->cnCodeService = $cnCodeService;
        $this->taricCodeService = $taricCodeService;
        $this->packageMaterialService = $packageMaterialService;
        $this->notificationCenter = $notificationCenter;
        $this->adminUser = $userService->findUserBy('id', $adminUserId);
        $this->em = $em;
        $this->formsCalculator = $formsCalculator;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'is_tender_offer',
                'category',
                'vendor'
            ]
        );

        $resolver->setDefaults(
            array(
                'data_class' => Product::class,
                'csrf_protection' => true,
                'csrf_field_name' => 'csrf_token',
                'csrf_token_id'   => 'csrf_token_product_item',
                'is_tender_offer' => false,
            )
        );
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isTenderOffer = $options['is_tender_offer'];
        $category = $options['category'];
        $this->vendor = $options['vendor'];
        $this->isTenderOffer = $isTenderOffer;
        $this->requirements = $this->requirementsService->getRequirementsForCategory($category);
        $this->fields = $this->fieldService->getFieldConfigs(($isTenderOffer)? FieldService::TENDER_OFFER_PRODUCT_FIELDS : FieldService::PRODUCT_FIELDS);
        $builder
            ->add(
                'category',
                EntityType::class,
                array(
                    'required' => true,
                    'class' => ProductCategory::class,
                    'constraints' => [new NotBlank()],
                    'mapped' => false,
                )
            )
            ->add(
                'existed_pictures',
                CollectionType::class,
                  array(
                      'required' => false,
                      'allow_add' => true,
                      'allow_delete' => true,
                      'entry_type' => TextType::class,
                      'mapped' => false,
                  )
            )
            ->add(
                'isActive',
                HiddenType::class,
                  array(
                      'required' => false,
                      'mapped' => false,
                  )
            );

        foreach ($this->requirements as $requirement) {
            $field = $requirement->getField();
            $fieldKey = $field->getFieldKey();

            // skip empty and unknown fields
            if ($fieldKey == '' || !isset($this->fields[$fieldKey]) || $field->getFieldType() == ProductField::FIELD_TYPE_GROUP) {
                continue;
            }

            $fieldData = $this->fields[$fieldKey];
            $fieldValues = $requirement->getPossibleValues();

            // skip vendor field here
            if (isset($fieldData['vendor']) && $fieldData['vendor']) {
                continue;
            }

            $formFieldConfig = array(
                'required' => false,
            );
            $formFieldType = (count($fieldValues) > 0) ? ChoiceType::class : TextType::class;
            $formFieldName = $fieldKey;

            // special warehouse type
            if (isset($fieldData['warehouse']) && $fieldData['warehouse']) {
                $formFieldType = EntityType::class;
                $formFieldConfig['class'] = Warehouse::class;
                $formFieldConfig['constraints'][] = new VendorWarehouse(array('vendor' => $this->vendor));
            } elseif (isset($fieldData['alcoholicCategory']) && $fieldData['alcoholicCategory']) {
                $formFieldType = EntityType::class;
                $formFieldConfig['class'] = AlcoholicCategory::class;
            } elseif (count($fieldValues) > 0) {
                // if we have predefined field values use them
                $choices = [];
                foreach ($fieldValues as $value) {
                    $choices[$value] = $value;
                }
                $formFieldConfig['choices'] = $choices;
            }

            //  taric code fields shouldn't be mapped
            if (isset($fieldData['taricCode']) && $fieldData['taricCode']) {
                $formFieldConfig['mapped'] = false;
                $formFieldConfig['constraints'][] = new TaricCode();
            }

            // add validation for price field
            $isNumber = false;
            if (($fieldData['number'] ?? false) || ($field->getFieldType() == ProductField::FIELD_TYPE_NUMBER)) {
                $isNumber = true;
                $formFieldConfig['constraints'][] = new Type('numeric');
            }

            if ($requirement->isNotBlank()) {
                $formFieldConfig['constraints'][] = new NotBlank();
                $formFieldConfig['required'] = true;
            }

            // translation fields shouldn't be mapped
            if (isset($fieldData['translation']) && $fieldData['translation']) {
                $formFieldConfig['mapped'] = false;
            }

            // price fields shouldn't be mapped
            if (isset($fieldData['price']) && isset($fieldData['priceName'])) {
                $formFieldConfig['mapped'] = false;
            }

            // for simple fields use their name instead of key
            if (isset($fieldData['simple']) && $fieldData['simple'] && isset($fieldData['field'])) {
                $formFieldName = $fieldData['field'];
            }

            $fieldComponent = $builder->create($formFieldName, $formFieldType, $formFieldConfig);
            if ($isNumber) {
                $fieldComponent->addModelTransformer(
                    new CallbackTransformer(
                        function ($dbValue) {
                            if ($dbValue === null) {
                                return null;
                            }

                            return str_replace('.', ',', $dbValue);
                        },
                        function ($userValue) {
                            if ($userValue === null) {
                                return null;
                            }

                            return str_replace(',', '.', $userValue);
                        }
                    )
                );
            }
            $builder->add($fieldComponent);
        }

        // add after customer submit event
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));

        // add before customer submit event
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [
                $this,
                'preSubmit'
            ]
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $this->formsCalculator->checkAndUpdateDataByFormula($this->requirements, $this->fields, $event);
    }

    /**
     * Set current customer additional data.
     *
     * @param FormEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        // If form isn't valid do nothing
        if (!$form->isValid()) {
            return;
        }

        /** @var Product $product */
        $product = $event->getData();

        $product->setVendor($this->vendor);

        $product->setPrice(0);
        if ($this->isTenderOffer) {
            $product->setType(Product::TYPE_TENDER_OFFER);
            $product->setPlainPrice('0');
            $product->setUnitPrice('0');
            $product->setMarketPrice('0');
            $product->setDiscount(0);
        } else {
            $product->setType(Product::TYPE_PRODUCT);
        }

        $category = $form->get('category')->getData();
        $product->getCategories()->clear();
        $product->addCategory($category);

        $packageMaterialAttributes = [];
        $attributesInfo = [];
        $pricesInfo = [];

        // bind translation fields here
        foreach ($this->requirements as $requirement) {
            $field = $requirement->getField();
            $fieldKey = $field->getFieldKey();

            // skip unknown fields
            if (!isset($this->fields[$fieldKey])) {
                continue;
            }

            $fieldData = $this->fields[$fieldKey];

            // skip vendor field
            if (isset($fieldData['vendor']) && $fieldData['vendor']) {
                continue;
            }
            // skip simple fields (they added by the form)
            if (isset($fieldData['simple']) && $fieldData['simple'] && isset($fieldData['field'])) {
                continue;
            }
            // skip unknown field for form
            if (!$form->has($fieldKey)) {
                continue;
            }

            $value = $form->get($fieldKey)->getData();

            // skip empty values
            if (($value !== 0) && ($value !== '0') && (!$value || $value == '')) {
                continue;
            }

            // translation field logic
            if (isset($fieldData['translation']) && $fieldData['translation']) {
                // this is translation
                $locale = '';
                if (isset($fieldData['locale'])) {
                    //@codeCoverageIgnoreStart
                    $locale = $fieldData['locale'];
                    //@codeCoverageIgnoreEnd
                } elseif (isset($fieldData['current_locale']) && $fieldData['current_locale']) {
                    $locale = $product->getCurrentLocale();
                }

                if (isset($fieldData['field'])) {
                    // this is translated field
                    $setter = 'set'.ucfirst($fieldData['field']);
                    if (!in_array($locale, $product->getLocales())) {
                        $product->makeNewTranslation($locale);
                        $product->updateTranslations();
                    }
                    $product->$setter($value, $locale);
                    if ($fieldData['field'] == 'title') {
                        $this->productService->generateUrl($product, $locale);
                    }
                    continue;
                }

                if (isset($fieldData['attribute'])) {
                    // this is attribute field
                    $attrName = $fieldData['attribute'];
                    if ($fieldData['package_material'] ?? false) {
                        $packageMaterialAttributes[$attrName] = true;
                        $attributesInfo[$attrName] = $value;
                    } else {
                        if (!isset($attributesInfo[$attrName])) {
                            $attributesInfo[$attrName] = [];
                        }
                        $attributesInfo[$attrName][$locale] = $value;
                    }
                }
            }

            if (isset($fieldData['price']) && isset($fieldData['priceName'])) {
                if (trim($value) == '0') {
                    continue;
                }
                // this is price field
                $priceName = $fieldData['priceName'];
                if (!isset($pricesInfo[$priceName])) {
                    $pricesInfo[$priceName] = [];
                }
                $pricesInfo[$priceName][$fieldData['price']] = $value;
            }

            if (isset($fieldData['taricCode'])) {
                $value = substr(trim($value), 0, 13);
                $taricCode = $this->taricCodeService->getTaricCode($value);
                if ($taricCode) {
                    $product->setTaricCode($taricCode);
                    $cnCode = substr($value, 0, -5);
                    if ($cnCodeEntity = $this->cnCodeService->getCnCode($cnCode)) {
                        $product->setCnCode($cnCodeEntity);
                    }
                }
            }
        }

        // init new or update existed attributes
        foreach ($attributesInfo as $attrName => $values) {
            /** @var ProductAttributeValue $attributeValue */
            $attributeValue = $product->getAttributeValue($attrName, true);
            if ($attributeValue) {
                if ($packageMaterialAttributes[$attrName] ?? false) {
                    // set package material
                    $packageMaterial = $this->packageMaterialService->getRepo()->find($values);
                    if ($packageMaterial instanceof PackageMaterial) {
                        $attributeValue->setPackageMaterial($packageMaterial);
                    } else {
                        $attributeValue->setPackageMaterial();
                    }
                } else {
                    foreach ($values as $locale => $value) {
                        if (!in_array($locale, $attributeValue->getLocales())) {
                            $attributeValue->makeNewTranslation($locale);
                            $attributeValue->updateTranslations();
                        }
                        $attributeValue->setValue($value, $locale);
                    }
                }
            } else {
                if ($packageMaterialAttributes[$attrName] ?? false) {
                    $attributeValue = $this->productAttributeValueService->create($product, $attrName, [], '', false);
                    // set package material
                    $packageMaterial = $this->packageMaterialService->getRepo()->find($values);
                    if ($packageMaterial instanceof PackageMaterial) {
                        $attributeValue->setPackageMaterial($packageMaterial);
                    } else {
                        $attributeValue->setPackageMaterial();
                    }
                } else {
                    $this->productAttributeValueService->create($product, $attrName, $values, '', false);
                }
            }
        }
        // remove attribute values
        foreach ($product->getAttributes() as $attributeValue) {
            $attrName = $attributeValue->getType()->getName();
            if (!isset($attributesInfo[$attrName])) {
                $product->removeAttributeValue($attributeValue);
            }
        }

        // update prices
        foreach ($pricesInfo as $priceName => $priceData) {
            $productPrice = $product->getPriceValue($priceName, true);
            $netPrice = $priceData[ProductPrice::NET_PRICE] ?? 0;
            $marketPrice = $priceData[ProductPrice::MARKET_PRICE] ?? 0;
            $unitPrice = $priceData[ProductPrice::UNIT_PRICE] ?? 0;
            $discount = $priceData[ProductPrice::DISCOUNT_PRICE] ?? 0;

            if ($productPrice) {
                $productPrice->setNetPrice($netPrice);
                $productPrice->setMarketPrice($marketPrice);
                $productPrice->setUnitPrice($unitPrice);
                $productPrice->setDiscount($discount);
            } else {
                $this->productPriceService->initPriceValue($product, $priceName, $netPrice, $marketPrice, $unitPrice, $discount, false);
            }
        }

        // remove not used pictures and update main picture
        $pictures = $form->get('existed_pictures')->getData();
        $removedPictureIds = [];
        $newMainPictureId = 0;
        foreach ($product->getPictures() as $i => $picture) {
            if (!empty($pictures) && $picture->getId() == $pictures[0] && !$picture->getIsMain()) {
                $newMainPictureId = $picture->getFile()->getId();
            }
            if (!in_array($picture->getId(), $pictures)) {
                $removedPictureIds[] = $picture->getFile()->getId();
                continue;
            }
        }
        if ($product->getId() && count($removedPictureIds)) {
            $this->productPictureService->remove($product->getId(), $removedPictureIds, false);
        }
        if ($newMainPictureId) {
            $this->productPictureService->makeMain($product->getId(), $newMainPictureId, false);
        }

        $product->clearPricesParts();

        // init gross prices
        $this->customsService->updateCustomsForProduct($product);
        $this->exciseService->updateExciseForProduct($product);
        $this->vatRateService->updateVatForProduct($product);
        $product->updateGrossPrices();

        $isActive = $form->get('isActive')->getData();
        if ($isActive !== null && (bool)$isActive !== $product->isActive()) {
            $this->offersService->setOfferIsActive($product, (bool)$isActive);
        }

        if ($product->doNotifyAdmin()) {
            $notificationMessage = 'Vendor updated taric code for product '.$product->getTitle().'. '.
            '<a href="/backend/#product_management;v1=list&v2=edit-product&v2-id='.$product->getId().'">Go here and active it</a>';
            $this->notificationCenter
                ->createNotificationBuilder($notificationMessage, md5($notificationMessage))
                ->addRecipient($this->adminUser)
                ->dispatch(['backend'])
            ;
        }

        // Update event data
        $event->setData($product);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'product';
    }
}

