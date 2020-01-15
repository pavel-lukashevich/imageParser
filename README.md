# Image Parser

A script that downloads all pictures from the site.
The url of the site from which you want to upload images is passed to the script as 
an argument to the call.

**Implementation Requirements:**
* Use an object oriented approach
* The url of the site from which you want to upload images must be passed to the script as
 an argument to the call
* Implement the ability to connect plugins that change or expand the behavior of the script
* Allowed to use libraries for http-requests and work with HTML

### Commands to run

1.  Clone this repository

```
git clone https://github.com/pavel-lukashevich/imageParser.git

```
2.  cd to the dir imageParser, follow

```
composer update
```

#Notification

* This script is not a universal solution. 
* For some sites, the script execution time can be very long.
* Images can take up a lot of space on your server, as they are saved for each page separately. 
This means that the same image can be in all folders if, for example, it is located in the header
 or footer of the site.
* No need to abuse downloading other people's content (but it's up to you) 


# ( ͡° ͜ʖ ͡°)

