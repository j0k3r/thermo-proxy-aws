# Thermo proxy AWS

![test](https://github.com/j0k3r/thermo-proxy-aws/workflows/test/badge.svg)

_TL;DR_: a little PHP app running on AWS Lambda that:

- receive data from the Sense Peanut App running on iOS or Android
- save them in DynamoDB & InfluxDB
- and provide a simple API to read them

## The long story now

By the end of 2015 the french Sense startup [launched some small connected devices](https://www.iphon.fr/post/capteurs-peanut-autonomes-sense-841214) and among them there were one to gather temperature: the **Thermo Peanut**. It gather the temperature and send it to a mobile app using Bluetooth. The app send these data to a server and you got some nice charts about temperature in return.

Late 2017 [the startup is in liquidation](https://www.mac4ever.com/actu/129742_sen-se-la-fin-des-peanuts). By the beginning of 2018 their servers became down for good. The app can still got the current temperature but charts are dead. And the app always try to send data to the server, killing the phone's battery.

I wasn't able to get a chance to read data from these connected devices using Bluetooth so I started looking at catching request from the app using a proxy.

I end up building that little PHP app to gather data and be able to retrieve them later.

## ⚠️ Don't buy a thermo peanut to use that project

As their servers are dead you won't be able to associate it to your phone. _And yes we can still found website which sells them..._

That project targets people who don't want to throw their thermo peanut away and who still have the app installed on a phone (which might stay at home all the time).

## Requirements

- a thermo peanut
- a phone with the Sense Peanut App installed
- a running instance of [InfluxDB](https://portal.influxdata.com/downloads/) (on an EC2 for example)
- a proxy to redirect traffic from the app ([Charles proxy](https://www.charlesproxy.com/) running on Raspberry Pi for example)
- the serverless framework [installed globally](https://serverless.com/framework/docs/getting-started/)
- an AWS account with [AWS credentials defined](https://serverless.com/framework/docs/providers/aws/guide/credentials/)

## Installation

1. Install deps

   ```
   composer install -o
   ```

2. You'll need information from your thermo peanut. The most important one is its MAC address (something like `00:0A:95:9D:68:16`). You can find it when you'll enable your proxy.

    Create the init file and then update `init_db.yml` with the MAC address.

    ```bash
    cp init_db.yml.dist init_db.yml
    ```

3. Define information about InfluxDB (user & pass can be empty):

    ```bash
    cp .env.dist .env
    ```

4. Deploy to your AWS account

    ```bash
    serverless deploy --verbose
    ```

5. Once deployed you'll have a dump inside the console with information about URLs of the API like

    ```bash
    Service Information
    service: thermo-proxy
    stage: dev
    region: eu-west-1
    stack: thermo-proxy-dev
    resources: 26
    api keys:
      None
    endpoints:
      POST - https://XXXXXXX.execute-api.eu-west-1.amazonaws.com/dev/peanut/api/v1/peanuts/{mac}/events
      GET - https://XXXXXXX.execute-api.eu-west-1.amazonaws.com/dev/thermo/{mac}/detail
      GET - https://XXXXXXX.execute-api.eu-west-1.amazonaws.com/dev/thermo/list
      GET - https://XXXXXXX.execute-api.eu-west-1.amazonaws.com/dev/thermo/init
    functions:
      api: thermo-proxy-dev-api
    layers:
      None
    ```

6. Init the DynamoDB table with data from `init_db.yml` (define at step 2) by browsing to `https://XXXXXXX.execute-api.eu-west-1.amazonaws.com/dev/thermo/init` (from the list above)

## Running locally

1. Run DynamoDB locally [using Docker](https://hub.docker.com/r/amazon/dynamodb-local/)

    ```bash
    docker run -p 8000:8000 amazon/dynamodb-local
    ```

2. Launch the app

   ```bash
   php -S localhost:8888 -t src src/App.php
   ```

3. Init the DynamoDB table by browsing to `http://localhost:8888/thermo/init`

## Using the proxy app

Now that the server is running, here are the endpoints available:

URL | Description | Response sample
-|-|-
`/thermo/list`|Return the list of your devices (previously inserted during the installation process)|[sample response](./data/list.json)
`/00:0A:95:9D:68:16/detail`|Return all information about the peanut|[sample response](./data/detail.json)

## Setup the proxy for the phone app

I'm using Charles to setup the proxy because I wasn't able to properly run a proxy to do the same job. I tried few solutions without success ([ssl-proxy](https://github.com/suyashkumar/ssl-proxy), [pound](http://www.apsis.ch/pound/), [mitmproxy](https://mitmproxy.org/) and [hiproxy](http://hiproxy.org/) to name a few). I often end up got nothing form the app after their first `CONNECT` request... 🤔

The phone app try to connect to a server at this address `https://app-00.sen.se:443` which means you have to redirect traffic to the API url on AWS.

So I setup a _Map Remote_ in Charles with these information:

![image](https://user-images.githubusercontent.com/62333/64076153-79755d80-ccc1-11e9-9772-bfd61f2e0e45.png)

Then I defined the proxy in the Wifi setting of the phone which runs the Sense Peanut App and I started to receive the data (all the way back from 2018, wow).

Everything is now running on Raspberry Pi Zero on the same network as the phone which runs the Sense Peanut App ✨
