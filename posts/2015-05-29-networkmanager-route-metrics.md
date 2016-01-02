---
title: Changing NetworkManager route metrics
---

If you use NetworkManager with more than one interface, you probably
want to control the metric of their routes. Here's one way you can do
it.

<!--more-->

## Background

I got a PCI Express WiFi card (`wlan0`) and a USB WiFi dongle
(`wlan1`). On my system, NetworkManager will activate both at the same
time. Then it gives metric 600 to the first one to be activated, and
601 to the second one.

What I wanted was to say: hey, NetworkManager, use metric 500 for
`wlan1` and 600 for `wlan0`. It doesn't look like this is supported,
though. Also, DuckDuckGo and Google didn't help me much.

Digging through the
[nm-settings(5) man page](https://www.mankier.com/5/nm-settings),
though, I found the route-metric option.  It's not ideal because it's
applied to a connection, not to a device.  But it works fine for my
use case as each WiFi interface is connected to a different WiFi
network.

### Step 1: Find out which are your connections
Use the nmcli helper to list your connections:

```
$ nmcli connection
NAME       UUID                                  TYPE             DEVICE
Network 1  f0ed603c-f3c3-4acb-b54b-bb857bd9c5b5  802-11-wireless  wlan0
Network 2  2b4b7240-36bd-407a-a3aa-169abb0ce6c4  802-11-wireless  wlan1
```

As you can see here, each interface is using a different connection.

### Step 2: Set the connection's default route metric

For example, to set the `wlan1` interface's default route metric to 500, just change its connection:

```
$ nmcli connection modify uuid 2b4b7240-36bd-407a-a3aa-169abb0ce6c4 ipv4.route-metric 500
$ nmcli connection modify uuid 2b4b7240-36bd-407a-a3aa-169abb0ce6c4 ipv6.route-metric 500
$ nmcli connection show   uuid 2b4b7240-36bd-407a-a3aa-169abb0ce6c4 | grep route-metric
ipv4.route-metric:                      500
ipv6.route-metric:                      500
```

### Step 3: Check that your routing table is correct

NetworkManager should automatically change the route's metric:

```
$ ip route
default via 192.168.25.1 dev wlan1  proto static  metric 500
default via 192.168.0.1 dev wlan0  proto static  metric 600
192.168.0.0/24 dev wlan0  proto kernel  scope link  src 192.168.0.111  metric 600
192.168.25.0/24 dev wlan1  proto kernel  scope link  src 192.168.25.69  metric 500
```

If it didn't, try restarting NetworkManager.
