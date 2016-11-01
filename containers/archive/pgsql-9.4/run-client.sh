#!/bin/sh

TAG="drupalci/pgsql-9.4"
CONTAINER_ID=$(docker ps | grep $TAG | awk '{print $1}')
IP=$(docker inspect --format='{{.NetworkSettings.IPAddress}}' $CONTAINER_ID)

echo $IP
PGPASSWORD=drupaltestbotpw psql -U drupaltestbot -h $IP
