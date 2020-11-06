# <b>Groundhogg Project Install and Dev Guide</b>
Follow this quick guide to get your local Groundhogg up and running. Talk to Nathan for any issues or questions with the React/npm sections.

# Setup Wordpress

Drumit please take care of this

# React Install and Development
React lives entirely within /src folder but all commands are run at the root level

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

#### Start Groundhogg
```
$ npm start
```

#### Pushing changes
Files can be manually pushed, or an ftp can be used. But symbolic links take care of all this. Use this command once with updated paths. This create a fake link pointing xamp outside of its file directory.

**** This command is un-tested. I believe it requires more options then the one I originally used.

```
mklink /J "C:\Link To Folder inside Xamp" "C:\repo\Groundhogg"
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
