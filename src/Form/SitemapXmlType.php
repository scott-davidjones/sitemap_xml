<?php

namespace Concrete\Package\SitemapXml\Form;

use Application\Bridge\Form\Type\PageSelectorType;
use Concrete\Core\Entity\Site\Locale;
use Concrete\Core\Site\Service;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\SitemapXml\Entity\SitemapXml;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SitemapXmlType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * @var array<string> $handlers
         */
        $handlers = $options['handlers'];
        /**
         * @var SitemapXml|null $data
         */
        $data = $builder->getData();
        $app = Application::getFacadeApplication();
        /**
         * @var Service $service
         */
        $service = $app->make(Service::class);
        $site = $service->getActiveSiteForEditing();
        $builder
            ->add('locale', EntityType::class, [
                'class' => Locale::class,
                'attr' => [
                    'data-behaviour' => 'locale-selector',
                ],
                'query_builder' => function (EntityRepository $repository) use ($site) {
                    return $repository->createQueryBuilder('l')
                        ->andWhere('l.site = :site')
                        ->setParameter('site', $site)
                        ->orderBy('l.siteLocaleID');
                },
                'choice_value' => function (?Locale $entity): ?int {
                    return $entity?->getLocaleID();
                },
                'choice_label' => function (Locale $locale): string {
                    return Countries::getName($locale->getCountry());
                }
            ])
            ->add('pageId', PageSelectorType::class, [
                'label' => 'Page',
                'required' => true,
                'attr' => ['class' => 'page-selector']
            ])
            ->add('title', TextType::class, [
                'required' => true
            ])
            ->add('fileName', TextType::class, [
                'required' => true
            ])
            ->add('limitPerFile', NumberType::class, [
                'label' => 'Limit Per File',
                'empty_data' => 50000,
                'required' => false,
            ])
            ->add('Handler', ChoiceType::class, [
                'label' => 'Type',
                'placeholder' => 'Select handler',
                'choices' => $handlers,
                'required' => false
            ])
            ->add('submit', SubmitType::class);
        if ($data && $data->getId()) {
            $builder->add('delete', SubmitType::class, [
                'label' => 'Delete',
                'attr' => [
                    'class' => 'btn btn-danger',
                    'name' => 'delete',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SitemapXml::class,
        ])
            ->setRequired('handlers');
    }
}
