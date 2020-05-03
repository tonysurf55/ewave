<?php

namespace App\Form;

use App\Entity\Upload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class UploadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('name')
            ->add('file', FileType::class, [
                'label' => false,
                'mapped' => true,
                'required' => true,

                // 'constraints' => [
                //     new File(
                //     [
                //         'maxSize' => '2M',
                //         'maxSizeMessage' => 'The file is too large (Limit 2Mb)'
                //     ])
                // ],
            ])
            // ->add('created')
            // ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Upload::class,
        ]);
    }
}
