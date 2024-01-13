# Step By Step
1. On the game server **(FreeBSD)** install the zip package using the command: **pkg install -y zip**.
2. Upload the script to your web server nd give it **permission 777**.
3. Edit the settings with your access data on **cron.php**.
4. Access the file from the browser to see if everything is right **(yourdomain.com/cron.php?api_protected=Papix)**.
5. Now to make the backups automatic just create the cron with the time you want.

# Notes
- For security reasons do not **forget** to **change** the **api key**.
- Do not store your backups in public directories, store them for example before the **/public_html** folder.
- Make sure that on the web server you use **directories that have permissions**.
- The script is not inserted into the game server **(FreeBSD)** but into the web server (I use **ubuntu**).

# Screenshots
![](https://i.epvpimg.com/oActdab.png)

![](https://i.epvpimg.com/0RReeab.png)
