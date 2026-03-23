<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\ReservationType as ReservationTypeEnum;
use App\Form\DataTransformer\TimeSlotTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your full name',
                    'maxlength' => 255,
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'your.email@example.com',
                    'maxlength' => 255,
                ],
                'required' => true,
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+1 (555) 123-4567',
                    'maxlength' => 50,
                ],
                'required' => true,
            ])
            ->add('reservationType', EnumType::class, [
                'class' => ReservationTypeEnum::class,
                'label' => 'Reservation Type',
                'choice_label' => fn (ReservationTypeEnum $type) => $type->getLabel(),
                'attr' => [
                    'class' => 'form-select',
                    'style' => 'cursor: pointer;',
                ],
                'required' => true,
                'placeholder' => 'Select reservation type',
                'help' => 'Regular: 1-10 guests, all days | Private: 6-12 guests, Fridays & Saturdays only',
            ])
            ->add('reservationDate', DateType::class, [
                'label' => 'Reservation Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTimeImmutable('+1 day'))->format('Y-m-d'),
                    'max' => (new \DateTimeImmutable('+30 days'))->format('Y-m-d'),
                    'style' => 'cursor: pointer;',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Please select a reservation date'),
                    new Assert\GreaterThanOrEqual(
                        value: new \DateTimeImmutable('tomorrow'),
                        message: 'Reservation date must be at least one day in the future'
                    ),
                    new Assert\LessThanOrEqual(
                        value: new \DateTimeImmutable('+30 days'),
                        message: 'Reservations can only be made up to 30 days in advance'
                    ),
                ],
            ])
            ->add('timeSlot', TextType::class, [
                'label' => 'Time Slot',
                'attr' => [
                    'class' => 'form-control',
                    'data-timeslot-select' => true,
                    'readonly' => true,
                    'placeholder' => 'Select date, type, and party size first',
                ],
                'required' => true,
                'help' => 'Available slots will appear based on your selections above',
            ])
            ->add('partySize', IntegerType::class, [
                'label' => 'Party Size',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 12,
                    'placeholder' => 'Number of guests',
                ],
                'required' => true,
                'help' => 'Number of guests (constraints apply based on reservation type)',
            ])
            ->add('specialRequests', TextareaType::class, [
                'label' => 'Special Requests',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 500,
                    'placeholder' => 'Any dietary restrictions, allergies, or special occasions? (Optional)',
                ],
                'help' => 'Maximum 500 characters',
            ])
        ;

        // Transformer to convert between string (H:i) and DateTimeInterface
        $builder->get('timeSlot')
            ->addModelTransformer(new TimeSlotTransformer())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
