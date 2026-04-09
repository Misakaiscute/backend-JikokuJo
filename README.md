# JikokuJó, a public transport helper.

## JikokuJó is trusty companion for finding your way around the streets of Budapest, created by Máté Demény and Hunor Horchy as part of their highschool exit masterpiece.

## Using offcial data from the local public transport provider (BKK), the app is capable of:
- ### 🔎 Searching for either line numbers or specific stops around Budapest for a given timeframe.
- ### 🗺️ Displaying the route of any local vehicle on the map.
- ### 📌 Following the line on its way if the given vehicle is equipped with trackers.
- ### ⭐ Storing users favorite trips and notifying them about it them depending on how the user set it up.
- ### ✈️ Sharing routes as links with friend to aid with planning.

## Using what?

### This part of the project is an API, powered by [PHP](https://www.php.net), using the [Laravel framework](https://laravel.com) for its ease of developing with. It's connected to a [MySQL](https://www.mysql.com) database, and is served to users with [NGINX](https://nginx.org).
### With the use of [Docker](https://www.docker.com) the entire application can be initialized and run with the use of a single command. This avoids version conflicts with users machines, and allows for a sanitized environment, thanks to the nature of Docker containers.
### The package manager for this project is [Composer](https://getcomposer.org).

## How to install
### Requirements:
- #### Docker Desktop and Docker CLI
- #### About 10 GB of free space
- #### Internet access

### Installation process:
1. #### Clone the repository into your selected directory
2. #### Editing the **.env.example** file if required, and renaming it to **.env**. (No need to change anything out of the box. Changing values in it is not recommended.)
3. #### Run the **docker compose up** command from the directory. This will start setting up the app
4. #### Wait for about 6-7 minutes until the Docker containers boot up, and the application fetches its initial data if required.
5. #### Go to **http://localhost:8000/api/(endpoint)** in your choosen browser.
6. #### 🏁You're good to go!🏁

## ⚠️Warning⚠️
### This is just one repository of the three, which make up the complete project. Whilst this repository may be able to be used your own applications, we still recommend checking out the [mobile app](https://github.com/Misakaiscute/mobile-JikokuJo) and the [web app](https://github.com/Misakaiscute/frontend-JikokuJo) for the full experience.