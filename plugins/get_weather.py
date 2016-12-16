#!/usr/bin/env python
   """
   Weather Grabber
   Pat O'Brien https://obrienlabs.net
   Description: Requires untangle. Grabs the weather information for the locations from Wunderground. Saves to JSON output for use elsewhere. Designed to run from crontab once an hour or so, to prevent constant checks against Weather Underground. 
   Version: 1.0
   """

import json, untangle

wx = {}
file = "/var/www/html/ops/plugins/weather.json"
city = {
    "la": "90067",
    "chicago": "60606",
    "springfield": "01115",
    "london": "pws:ILONDON73",
    "sydney": "YSSY",
    "hongkong": "pws:IROYALOB5",
    "tokyo": "HND",
}

# Build the wx dictionary. Key is the city, value is the zip code
for key, value in city.iteritems():
    xml = untangle.parse("http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=%s" % value)
    updated = xml.current_observation.observation_time.cdata
    temp_f = xml.current_observation.temp_f.cdata
    icon = xml.current_observation.icon.cdata
    
    # Find the right icon URL to use
    for x in xml.current_observation.icons.icon_set:
        if x["name"] == "Incredible":
            iconurl = x.icon_url.cdata

    # Check to make sure we have a valid location by seeing if there is temperature data
    if ( xml.current_observation.temp_f.cdata is None or xml.current_observation.temp_f.cdata == "" ):
        wx[key] = {"updated":updated, "temp_f":"Error, invalid location", "icon":icon, "iconurl":iconurl}
    else:
        # Build dictionary
        wx[key] = {"updated":updated, "temp_f":temp_f, "icon":icon, "iconurl":iconurl}


# Convert dictionary to JSON
output = json.dumps(wx)

# Save to file
try:
    print "Saving weather data to %s" % file
    output_file = open(file, "w") # Overwrite file
    output_file.write(output + '\n')
    output_file.close()
except:
    print "Cannot write to %s" % file
    pass
