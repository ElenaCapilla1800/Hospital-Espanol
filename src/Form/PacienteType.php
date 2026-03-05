<?php

namespace App\Form;

use App\Entity\Paciente;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;

/**
 * Esta clase define el "molde" del formulario para los pacientes.
 * Symfony usa este archivo para generar el HTML y validar los datos recibidos.
 */
class PacienteType extends AbstractType
{
    /**
     * Aquí construimos los campos del formulario uno a uno.
     */
    public function buildForm(FormBuilderInterface $constructor, array $opciones): void
    {
        $constructor
            // 1. Campo para el Nombre
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del Paciente',
                'attr' => [
                    'placeholder' => 'Ej: Juan',
                    'class' => 'form-control' // Clase de Bootstrap para que se vea bien
                ]
            ])

            // 2. Campo para los Apellidos
            ->add('apellidos', TextType::class, [
                'label' => 'Apellidos completos',
                'attr' => ['class' => 'form-control']
            ])

            // 3. Campo para el DNI (Identificación única)
            ->add('dni', TextType::class, [
                'label' => 'DNI / NIE / Pasaporte',
                'attr' => ['class' => 'form-control']
            ])

            // 4. Campo para el Teléfono (Usamos TelType para teclados móviles)
            ->add('telefono', TelType::class, [
                'label' => 'Teléfono de contacto',
                'required' => false, // Es opcional según tu entidad
                'attr' => ['class' => 'form-control']
            ])

            // 5. Campo para la Fecha de Nacimiento
            ->add('fechaNacimiento', DateType::class, [
                'label' => 'Fecha de Nacimiento',
                'widget' => 'single_text', // Muestra el selector de fecha nativo del navegador
                'input' => 'datetime_immutable', // Indica que debe guardarse como este tipo de objeto
                'attr' => ['class' => 'form-control']
            ])

            // 6. Campo para el Historial Clínico (Cuadro de texto grande)
            // Este campo ahora se conectará correctamente con tu propiedad en la Entidad
            ->add('historialClinico', TextareaType::class, [
                'label' => 'Notas del Historial Médico',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Escriba aquí los antecedentes o notas generales...'
                ]
            ])
        ;
    }

    /**
     * Esta función vincula este formulario directamente con la Entidad Paciente.
     */
    public function configureOptions(OptionsResolver $configurador): void
    {
        $configurador->setDefaults([
            'data_class' => Paciente::class, // Symfony sabe que los datos van a la tabla 'Paciente'
        ]);
    }
}
