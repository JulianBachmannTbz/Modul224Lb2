# Modul224Lb2
How To Use my Project:
1. Pull all files into new Project
2. Change the host and run this code on your IOT Kit
//
#include "mbed.h"
#if MBED_CONF_IOTKIT_HTS221_SENSOR == true
#include "HTS221Sensor.h"
#endif
#if MBED_CONF_IOTKIT_BMP180_SENSOR == true
#include "BMP180Wrapper.h"
#endif
#include "http_request.h"
#include "OLEDDisplay.h"

// UI
OLEDDisplay oled( MBED_CONF_IOTKIT_OLED_RST, MBED_CONF_IOTKIT_OLED_SDA, MBED_CONF_IOTKIT_OLED_SCL );
DigitalOut led1( MBED_CONF_IOTKIT_LED1 );

static DevI2C devI2c( MBED_CONF_IOTKIT_I2C_SDA, MBED_CONF_IOTKIT_I2C_SCL );
#if MBED_CONF_IOTKIT_HTS221_SENSOR == true
static HTS221Sensor hum_temp(&devI2c);
#endif
#if MBED_CONF_IOTKIT_BMP180_SENSOR == true
static BMP180Wrapper hum_temp( &devI2c );
#endif

/** ThingSpeak URL und API Key ggf. anpassen */
char host[] = "http://192.168.104.21/modul224lb2/dataApi.php";
char key[] = "";

// I/O Buffer
char message[1024];

DigitalOut myled(MBED_CONF_IOTKIT_LED1);

int main()
{
    uint8_t id;
    float value1, value2;

    printf("\tThingSpeak\n");

    /* Init all sensors with default params */
    hum_temp.init(NULL);
    hum_temp.enable();

    hum_temp.read_id(&id);
    printf("HTS221  humidity & temperature    = 0x%X\r\n", id);

    // Connect to the network with the default networking interface
    // if you use WiFi: see mbed_app.json for the credentials
    WiFiInterface* network = WiFiInterface::get_default_instance();
    if (!network) {
        printf("ERROR: No WiFiInterface found.\n");
        return -1;
    }

    printf("\nConnecting to %s...\n", MBED_CONF_APP_WIFI_SSID);
    int ret = network->connect(MBED_CONF_APP_WIFI_SSID, MBED_CONF_APP_WIFI_PASSWORD, NSAPI_SECURITY_WPA_WPA2);
    if (ret != 0) {
        printf("\nConnection error: %d\n", ret);
        return -1;
    }

    printf("Success\n\n");
    printf("MAC: %s\n", network->get_mac_address());
    SocketAddress a;
    network->get_ip_address(&a);
    printf("IP: %s\n", a.get_ip_address());

    while( 1 )
    {
        hum_temp.get_temperature(&value1);
        hum_temp.get_humidity(&value2);

        sprintf( message, "%s?temperatur=%f&humidity=%f", host, value1, value2 );
        printf( "%s\n", message );
        oled.cursor( 0, 0 );
        oled.printf( "temp: %3.2f\nhum : %3.2f", value1, value2 );
        if(value1>25){
            myled = 1;
            led1 = 1;
            printf("Temperatur Higher than 25 Degree");
            printf("%f", value1);
        }
        else{
            myled = 0;
            led1 = 0;
            printf("Temperatur lower than 25 Degree");
            printf("%f", value1);
        }
        HttpRequest* get_req = new HttpRequest( network, HTTP_GET, message );

        HttpResponse* get_res = get_req->send();
        if (!get_res)
        {
            printf("HttpRequest failed (error code %d)\n", get_req->get_error());
            return 1;
        }
        delete get_req;
        thread_sleep_for( 10000 );
    }
}
