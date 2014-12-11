README.md
=========
What is Drafterbit ?
--------------------
Drafterbit is web app you can use to create a website. Currently still in early phase.

Requirement
-----------
- Web server, Apache httpd 2.2 or newer with mod_rewrite enabled. (you probably can use Nginx but not tested yet).
- PHP 5.4 or newer

Install
-------
Drafterbit installation package will be available for download soon. For now you can just do the following step to get it running:

1. Make sure that you have [composer](https://getcomposer.org/) and [bower](https://bower.io/) installed in your computer.
2. Open Terminal then go to web doc root. e.g:
    ```shell
    cd /var/www/
    ```
    
3. Clone this repo
    ```shell
    git clone https://github.com/drafterbit/drafterbit.git
    ```
    
4. Go to cloned directory then install php dependencies:
    ```shell
    cd drafterbit
    composer install --no-div --prefer-dist
    ```
    
5. Wait, then install web dependencies
    ```shell
    bower install
    ```
    
6. From here, you can visit browser, e.g: `http://localhost/drafterbit` then follow installation instruction as usual.

Please let me know if you get any problem.

Learn
-------------
There is still no documentation at all yet, but for now you can just ask me anything anywhere anytime thru gitter : [![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/drafterbit/drafterbit?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge).

Contribute
----------
Drafterbit is an open source and intended to be community-driven project. Any kind of contribution are really appreciated. Just fork and make a Pull Request.

License
-------
Drafterbit is licensed under the MIT license.