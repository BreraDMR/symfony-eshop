<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\CheckoutDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CheckoutDetails>
 */
class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Full name',
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Shipping address',
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckoutDetails::class,
        ]);
    }
}
