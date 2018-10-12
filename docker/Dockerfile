FROM wordpress
ADD functions.php functions.php
ADD basic-auth.php basic-auth.php
ADD proxy-config.php proxy-config.php
ADD docker-entrypoint.sh docker-entrypoint.sh
RUN cat functions.php >> ../../../../usr/src/wordpress/wp-content/themes/twentyseventeen/functions.php
RUN cat basic-auth.php >> ../../../../usr/src/wordpress/wp-content/plugins/basic-auth.php
RUN  cat proxy-config.php >> ../../../../usr/src/wordpress/wp-config-sample.php
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
