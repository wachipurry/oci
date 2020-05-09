<?php

namespace App\Controller;

use App\Entity\Pelicula;
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
        } else {
            // FALTARIA COMPROBAR LOS OTROS TIPOS DE ACTIVIDADES PERO ES DEMASIADO TRABAJO YA
            if (isset($_POST['peliculas'])) {
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

    /**
     * @Route("/oci/add", name="oci_add")
     */
    public function addPelicula()    {
        return $this->render('peliculas/addPelicula.html.twig');
    }

    /**
     * @Route("/oci/api", name="oci_api")
     */
    public function apiPelicula()
    {
        $client = new NativeHttpClient();
        if (isset($_POST['add'])) {
            empty($_POST['nombre']) ?  $nombre = "" :  $nombre = $_POST['nombre'];
            empty($_POST['genero']) ?  $genero = "" :  $genero = $_POST['genero'];
            empty($_POST['descripcion']) ?  $descripcion = "" :  $descripcion = $_POST['descripcion'];
            $response = $client->request('POST', 'http://127.0.0.1:8080/api/pelicula' , [
                'json' => ['nombre' => $nombre, 'genero' => $genero, 'descripcion' => $descripcion]
            ]);
        } else if (isset($_POST['update'])) {
            empty($_POST['id']) ?  $id = "" :  $id = $_POST['id'];
            empty($_POST['nombre']) ?  $nombre = "" :  $nombre = $_POST['nombre'];
            empty($_POST['genero']) ?  $genero = "" :  $genero = $_POST['genero'];
            empty($_POST['descripcion']) ?  $descripcion = "" :  $descripcion = $_POST['descripcion'];
            $response = $client->request('PUT', 'http://127.0.0.1:8080/api/pelicula/'.$id , [
                'json' => ['nombre' => $nombre, 'genero' => $genero, 'descripcion' => $descripcion]
            ]);
        } else if (isset($_POST['delete'])) {
            empty($_POST['id']) ?  $id = "" :  $id = $_POST['id'];
            $response = $client->request('DELETE', 'http://127.0.0.1:8080/api/pelicula/' . $id);
        } else {
            return $this->redirect('/oci');
        }
        return $this->redirect('/oci');
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
            $response = $client->request('GET', 'http://127.0.0.1:8080/api/pelicula/' . $id);
            if ($response->getStatusCode() == '200') {
                $pelicula = json_decode($response->getContent());

                return $this->render('peliculas/index.html.twig', [
                    'controller_name' => 'OciController', "pelicula" => $pelicula
                ]);
            }
        }
        else{
            return $this->redirect('/oci');
        }
        return $this->redirect('/oci');
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
