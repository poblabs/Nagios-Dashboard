# Nagios Dashboard
A clean and simple overview of your Nagios environment optimized for wall TV displays. 

I designed this dashboard **in my spare time** as a way to have quick visibility into the health of the Nagios environment from a TV mounted on the wall in our public space - or on a tablet on your desk. It offers a color coded system which helps stand out any troubles that are going on and automatic background updates mean you don't have to constantly refresh the page.

![alt text](http://i.imgur.com/BmZ4CxB.jpg "Nagios Dashboard")

## Clean and focused on troubles
* If a host is OK, it will not show on the dashboard
* If a host has notifications disabled, it will not show on the dashboard
* Any host or service in trouble will show on the board
* A clear top banner which is green if any troubles have been acknowledged, or no troubles exist
* If there are any active troubles, and acknowledged troubles, the top banner will only focus on those which have not been acknowledged
* Separation of active versus acknowledged for a quick glance on what troubles need to be focused on

## Background AJAX updates
Set it and forget it. The dashboard will constantly refresh the body content with new alerts. Default is 5 seconds, but it is configurable. 

## Team filtering
The dashboard is also hostgroup aware. If you have multiple teams that use your Nagios system, you can group their items into a hostgroup, then give them the URL for their items (e.g. https://nagios/ops/hostgroup/. This way each team can have their own wall display, or monitor, showing only the items they are responsible for. 

## Mobile responsive design
You can check the dashboard from your mobile devices, the design will change to try and fit everything in. 

![alt text](http://i.imgur.com/Q60DgLml.png "Nagios Dashboard Mobile")

## License
Licensed under the MIT License. A copy of the LICENSE is included with this project.

## Pull Requests Welcome
If you see something wrong - or you want to contribute - just fork the project make your changes and submit a pull request!
