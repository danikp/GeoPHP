#!/usr/bin/env bash

wget https://github.com/libgeos/libgeos/archive/$VERSION.tar.gz
tar zxf $VERSION.tar.gz
cd libgeos-$VERSION
./autogen.sh
./configure
make
sudo make install

git clone https://git.osgeo.org/gogs/geos/php-geos.git
cd php-geos
./autogen.sh
./configure
make # generates modules/geos.so
mv modules/geos.so $(php-config --extension-dir)
cd ..
echo "extension=geos.so" > geos.ini
phpenv config-add geos.ini
