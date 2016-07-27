#!/bin/sh
exec rsync -rvz _site/ ubuntu@banana.felipe.lessa.nom.br:/var/www/meteficha.com/
