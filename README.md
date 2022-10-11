This module integrates a Magento 2 based webstore with the [**2Checkout**](https://www.2checkout.com/) payment service.  
The module is **free** and **open source**.

## Demo videos
1. [**Capture** a payment](https://mage2.pro/t/1653).
2. [Partially **refund** a payment from the **Magento** side](https://mage2.pro/t/1736).
3. [**Refund** a payment from the **2Checkout** side](https://mage2.pro/t/1747).

## How to install
[Hire me in Upwork](https://www.upwork.com/fl/mage2pro), and I will: 
- install and configure the module properly on your website
- answer your questions
- solve compatiblity problems with third-party checkout, shipping, marketing modules
- implement new features you need 

### 2. Self-installation
```
bin/magento maintenance:enable
rm -f composer.lock
composer clear-cache
composer require mage2pro/2checkout:*
bin/magento setup:upgrade
bin/magento cache:enable
rm -rf var/di var/generation generated/code
bin/magento setup:di:compile
rm -rf pub/static/*
bin/magento setup:static-content:deploy -f en_US <additional locales>
bin/magento maintenance:disable
```

## How to update
```
bin/magento maintenance:enable
composer remove mage2pro/2checkout
rm -f composer.lock
composer clear-cache
composer require mage2pro/2checkout:*
bin/magento setup:upgrade
bin/magento cache:enable
rm -rf var/di var/generation generated/code
bin/magento setup:di:compile
rm -rf pub/static/*
bin/magento setup:static-content:deploy -f en_US <additional locales>
bin/magento maintenance:disable
```
