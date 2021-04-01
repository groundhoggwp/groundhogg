# <b>Groundhogg Project Install and Dev Guide</b>
Follow this quick guide to get your local Groundhogg up and running. Talk to Nathan for any issues or questions with the React/npm sections.

# Setup Wordpress

Follow https://www.wpbeginner.com/wp-tutorials/how-to-install-wordpress-on-your-windows-computer-using-wamp/ or https://themeisle.com/blog/install-xampp-and-wordpress-locally/ to set up local wordpress environment.

# React Install and Development
React lives entirely within /src folder but all commands are run at the root level

#### Turn on Script Debugging
Add this line to wp-config.php. This file is located outside of the repo but within htdocs of your XAMP or WAMP install. Restart your environment after. This puts you in the development mode to see full React errors.
```
define('SCRIPT_DEBUG', true);
```

#### React Repository Structure

```bash
├── src
│   ├── components
│       ├── core-ui #All re-usuable components
│       └── layout #Routing and top level components
│   ├── data #All data stores to communicate with REST API
│   ├── documentation
│   ├── utils
│   └── pages
│   └── index.js
│   └─README.md
```

#### Install the System Dependencies
Both node and npm are required <br>
https://nodejs.org/en/download/

#### Verify the Dependencies
Open a prompt and run these commands
```
$ node -v
$ npm -v
```


#### Install Groundhogg's npm libraries
```
$ npm i
```

#### Install Python
Nothing is written in python but you'll need it for webpack. https://www.python.org/downloads/


#### Start Groundhogg
```
$ npm start
```

#### Symbolic Link
A symbolic links is used to link your repo to XAMP's directory. Below is an example of windows command to set it up. But a full guide can be found here: https://www.maketecheasier.com/create-symbolic-links-windows10/


```
mklink /J C:\xampp\htdocs\wordpress\wp-content\plugins\groundhogg C:\repo\Groundhogg\core
```

# Development Tips

#### Material UI
Material UI powers much of this application. Many of the components are modifications from copy & paste examples. Consult core-ui and material ui before building any type of common component.

#### Styling
Material UI makeStyles function eliminates the need for CSS files. Although all CSS values remain the same. Properties lose "-" and are in camel case. A full list can be found here

https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Properties_Reference


#### Data Stores
We have a base generic store that handles most CRUD functions. Other data stores can extend it or use it as is.

#### React Hooks
We almost only use React hooks in our components. Avoid classes as they cause clashes with makeStyles and Material UI.

#### Luxon
Luxon isn't used widely in our application but it is the date library to use.

#### Prettier
Prettier is installed and can be used at your leisure to update individual files you work on. Don't run any large recursive calls yet as it isn't implemented into our commit and deployment methods.
