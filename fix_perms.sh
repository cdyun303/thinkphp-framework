#!/bin/sh

#目录权限755
find . -type d -exec chmod 755 {} \;
#文件权限644
find . -type f -exec chmod 644 {} \;

# runtime
if [ -d "./runtime" ]; then
chmod -R 777 runtime
fi

# bucket
if [ -d "./public/bucket" ]; then
chmod -R 777 ./public/bucket
fi

echo "Permissions fixed."