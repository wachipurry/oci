<?php

namespace App\Controller;

use App\Form\PreferencesFormType;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;

class OciController extends AbstractController
{
    /**
     * @Route("/oci", name="oci")
     */
    public function loadOciPage()
    {
        $client = new NativeHttpClient();
        $registrado = false;
        $preferencias = [];
        $actividades = [];

        $usuario = "";

        if (!empty($this->getUser())) {
            $registrado = true;
            $preferencias = $this->getUser()->getPreferencias();
            $usuario = $this->getUser()->getUsername();
            $preferencias = $this->loadPreferencias($preferencias);
            for ($i = 0; $i < count($preferencias); $i++) {
                $response = $client->request('GET', 'http://127.0.0.1:8080/api/' . $preferencias[$i]);
                if ($response->getStatusCode() == '200') {
                    if ($preferencias[$i] == "peliculas") {
                        $actividades = array_merge($actividades, json_decode($response->getContent()));
                    }
                    //SOY CONSCIENTE QUE LAS ACTIVIDADES QUE NO SEAN PELICULAS
                    // NO SE VAN A ANADIR PERO COMO NO ESTÁN DEFINIDAS COMO TAL
                    // EN EL FORMULARIO DE REGISTRO SI SE PERMITE METER OTRAS PREFERENCIAS
                    // PERO NO LAS VOY A TRATAR
                }
            }
        }
        else{
            // FALTARIA COMPROBAR LOS OTROS TIPOS DE ACTIVIDADES PERO ES DEMASIADO TRABAJO YA
            if(isset($_POST['peliculas'])){
                $response = $client->request('GET', 'http://127.0.0.1:8080/api/peliculas');
                if ($response->getStatusCode() == '200') {
                    $actividades = array_merge($actividades, json_decode($response->getContent()));
                }
            }
        }
        return $this->render('oci/index.html.twig', [
            'controller_name' => 'OciController', "usuario" => $usuario, "registrado" => $registrado, "actividades" => $actividades
        ]);
    }

    // AQUI MIRARIA QUE TIPO DE ACTIVIDAD ES PERO SUPONGAMOS QUE SOLO LLEGAN
    // PELICULAS
    /**
     * @Route("/oci/{id}", name="oci_id")
     */
    public function loadOneActivity($id)
    {
        if (!empty($this->getUser())) {
            $client = new NativeHttpClient();
            $response = $client->request('GET', 'http://127.0.0.1:8080/api/pelicula/'.$id);
            if ($response->getStatusCode() == '200') {
                $pelicula= json_decode($response->getContent());

                return $this->render('peliculas/index.html.twig', [
                    'controller_name' => 'OciController', "pelicula" => $pelicula
                ]);
            }
        }
    }

    private function loadPreferencias($preferencias)
    {
        $preferenciasTexto = [];
        if (in_array("1", $preferencias)) {
            array_push($preferenciasTexto, "peliculas");
        }
        if (in_array("2", $preferencias)) {
            array_push($preferenciasTexto, "opera");
        }
        if (in_array("3", $preferencias)) {
            array_push($preferenciasTexto, "teatro");
        }
        if (in_array("4", $preferencias)) {
            array_push($preferenciasTexto, "excursiones");
        }

        return $preferenciasTexto;
    }
    /**
     * @Route("/oci/home", name="oci_registered")
     */
    public function loadOciHomePage(Request $request)
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    public function index()
    {
        return $this->render('oci/index.html.twig', [
            'controller_name' => 'OciController',
        ]);
    }
}
