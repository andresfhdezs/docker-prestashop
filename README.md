# Manejo de Contenedores Docker

* Listar los contenedores           = sudo docker ps -a

* Eliminar los contenedores         = sudo docker rm <CONTENEDOR_ID>
* Eliminar todos los contenedores   = sudo docker rm `sudo docker ps -a -q`

_Ver la ip del contenedor_
sudo docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' <CONTENEDOR_ID>

# Manejo de Imagen Docker

* Crear una imagen                  = docker build --build-arg -t <NOMBRE_IMAGEN>

* Listar las imagen                 = sudo docker image ls

* Eliminar las image                = sudo docker rmi <IMAGE_ID>
* Eliminar todas las image          = sudo docker rmi -f `sudo docker images -q`

* Ver contenido de imagen           = docker run -it image_name sh

# Manejo de Docker-compose 

* Iniciar docker-compose                            = sudo docker-compose up
* Inniciar docker-compose segundo plano             = sudo docker-compose up -d
* Detener el docker-compose                         = sudo docker-compose down
* ver log de docker compose                         = sudo docker-compose logs -f -t
* Detener un Docker-Compose y eliminar volúmenes    = sudo docker-compose down -v
* Ver Docker-Compose en ejecución                   = sudo docker-compose ps
* Eliminar Docker-Compose detenidos                 = sudo docker-compose rm

## Conceptos de Dockerfile

* MAINTAINER: Nos permite configurar datos del autor, principalmente su nombre y su dirección de correo electrónico.
* ENV: Configura las variables de entorno.
* ADD: Esta instrucción se encarga de copiar los ficheros y directorios desde una ubicación especificada y los agrega al sistema de ficheros del contenedor. Si se trata de añadir un fichero comprimido, al ejecutarse el guión lo descomprimirá de manera automática.
* COPY: Es la expresión recomendada para copiar ficheros, similar a ADD.
* EXPOSE: Indica los puertos en los que va a escuchar el contenedor. Hay que tener en cuenta que esta opción no consigue que los puertos sean accesibles desde el host; para esto debemos utilizar la exposición de puertos mediante la opción -p de docker run, tal y como explicamos en un artículo anterior.
* VOLUME: Esta es una opción que muchos usuarios de la Web estaban esperando como agua de mayo. Nos permite utilizar en el contenedor una ubicación de nuestro host, y así, poder almacenar datos de manera permanente. Los volúmenes de los contenedores siempre son accesibles en el host anfitrión, en la ubicación: /var/lib/docker/volumes/
* WORKDIR: El directorio por defecto donde ejecutaremos las acciones.
* USER: Por defecto, todas las acciones son realizadas por el usuario root. Aquí podemos indicar un usuario diferente.
* SHELL: En los contenedores, el punto de entrada es el comando /bins/sh -c para ejecutar los comandos específicos en CMD, o los comandos especificados en línea de comandos para la acción run.
* ARG: Podemos añadir parámetros a nuestro Dockerfile para distintos propósitos.