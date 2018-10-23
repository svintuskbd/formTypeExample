<?php

namespace AppBundle\Service;

use ChrisKonnertz\StringCalc\Exceptions\ContainerException;
use ChrisKonnertz\StringCalc\Exceptions\NotFoundException;
use ChrisKonnertz\StringCalc\StringCalc;
use Doctrine\ORM\EntityManager;
use ****\ProductRequirementsBundle\Entity\CategoryRequirements;
use ****\ProductRequirementsBundle\Entity\ProductField;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Class FormsCalculatorService
 * @package AppBundle\Service
 */
class FormsCalculatorService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * FormsCalculator constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param CategoryRequirements[] $requirements
     * @param                        $fields
     * @param FormEvent              $event
     */
    public function checkAndUpdateDataByFormula($requirements, $fields, FormEvent $event)
    {
        $form = $event->getForm();
        $eventData = $event->getData();

        foreach ($requirements as $cat) {
            $fieldKeyReq = $cat->getField()->getFieldKey();
            if ($cat->getField()->getFieldType() == ProductField::FIELD_TYPE_CALCULATED
                && isset($fields[$fieldKeyReq])) {
                $f = $cat->getField()->getFormula();

                if (!$f) {
                    continue;
                }

                preg_match_all('/\[(\d+)\]/', $f, $matches);

                foreach ($matches[1] as $match) {
                    $productField = $this->getProductField($match);
                    $fieldKey = $productField->getFieldKey();

                    if (!isset($eventData[$fieldKey])) {
                        $value = 0;
                    } else {
                        $value = $eventData[$fieldKey];
                        if ($value == 0 || $value == '0' || $value == '') {
                            $value = 0;
                        }
                    }

                    $f = str_ireplace('[' . $match . ']', $value, $f);
                }

                // form calculate
                $this->calculate($form, $f, $fieldKeyReq);
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param string $f
     * @param string $fieldKeyReq
     */
    private function calculate(FormInterface $form, $f, $fieldKeyReq)
    {
        try {
            $stringCalc = new StringCalc();
            $result = $stringCalc->calculate($f);
            $form->get($fieldKeyReq)->setData($result);
        } catch (\Exception $e) {
        }
    }

    /**
     * @param $productFieldId
     * @return ProductField
     */
    private function getProductField($productFieldId)
    {
        return $this->em->getRepository(ProductField::class)->find($productFieldId);
    }
}

