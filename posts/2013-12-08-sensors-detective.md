---
title: Sensors detective
---

I have an Asus M5A97 PRO motherboard, which includes the common set of
sensors.  The lm-sensors free software correctly detects two chips,
`k10temp-pci-00c3` and `it8721-isa-0290`, corresponding to my Phenom
II and my motherboard, respectively.  However, by default the output
of sensors looks like:

<!--more-->

```python
k10temp-pci-00c3
Adapter: PCI adapter
temp1:        +27.0°C  (high = +70.0°C)
                       (crit = +90.0°C, hyst = +85.0°C)

it8721-isa-0290
Adapter: ISA adapter
in0:          +2.82 V  (min =  +0.62 V, max =  +0.62 V)  ALARM
in1:          +2.81 V  (min =  +0.06 V, max =  +0.34 V)  ALARM
in2:          +1.30 V  (min =  +1.66 V, max =  +1.12 V)  ALARM
+3.3V:        +3.29 V  (min =  +4.68 V, max =  +0.79 V)  ALARM
in4:          +0.61 V  (min =  +0.36 V, max =  +0.94 V)
in5:          +2.52 V  (min =  +0.14 V, max =  +1.01 V)  ALARM
in6:          +0.00 V  (min =  +1.73 V, max =  +0.11 V)  ALARM
3VSB:         +0.77 V  (min =  +0.24 V, max =  +4.13 V)
Vbat:         +3.36 V
fan1:        1288 RPM  (min =  105 RPM)
fan2:        1192 RPM  (min =   16 RPM)
fan3:           0 RPM  (min =   10 RPM)  ALARM
temp1:        +37.0°C  (low  = +72.0°C, high = +10.0°C)  ALARM  sensor = thermistor
temp2:        +30.0°C  (low  = +15.0°C, high = -128.0°C)  ALARM  sensor = thermistor
temp3:       -128.0°C  (low  = -55.0°C, high = +49.0°C)  sensor = disabled
intrusion0:  OK
```

Well, not exactly useful.  But it turns out that we can do better than
that with a little work. The <a
href="http://www.lm-sensors.org/wiki/VoltageLabelsAndScaling">lm-sensors
wiki page about voltage labels and scaling</a> describes a simple
method by which one may make sense of their motherboard's sensors.

My motherboard's sensors chip model is IT8721F, which measures raw
values ranging from 0 to 3.06V in steps of 12 mV (8 bits of
precision).  In order to fit its range, voltage rails such as +5V get
a scaling factor.  Our job is to figure out which is the scaling
factor and which input of the sensor is measuring it.

We start by collecting samples of the correct data.  I didn't bother
resetting my computer to look at my own BIOS, and instead just
searched the web for screenshots of M5A97's BIOS screen.  In the end I
had collected the following samples:

```python
Temperature:
  CPU: 33.0 37.0 39.0 40.0 42.0 44.0
  MB:  30.0 31.0 32.0 37.0

Voltage (it8721, 0 to 3.06 V, 1 bit = 12 mV):
  Vcore:  1.248  1.308  1.320  1.332  1.356  1.380  1.428  1.440
  +3.3V:  3.216  3.264  3.288  3.312  3.336  3.360

  +5V:    4.885  4.971  4.992  5.057
           --- 86 --- 21 --- 65 ---
               =             =
          2*21 + 2*22     21 + 2*22

  +12V:  11.685 11.943 11.994 12.046 12.097 12.149 12.200
           -- 258 -- 51 --- 52 --- 51 --- 52 --- 51 ----
               =
          2*51 + 3*52
```

The +3.3V sensor had already been correctly named by lm-sensors (in3).
The Vcore sensor (CPU) is quite easy since it's not scaled (in2). +5V
and +12V are the tricky ones.

As you can see on the snippet above, I've sorted the samples I've
collected and calculated the differences in mV between them.  Looking
at the +12V samples first, we see a pattern of alternately adding 51
or 52 mV, thus we deduce that the measurements have steps of 51.5 mV
between them.  This works out to a scaling factor of 51.5/12.  The +5V
is analogous and we calculate a scaling factor of 21.5/12 for it.

(Up to this point, something has been bothering me.  If we take a look
at sample 12.200 V, for example, we'd expect it to be a multiple
of 51.5 mV.  However, the closest multiple is 237 * 51.5 mV = 12.2055
V.  No matter how you round that number, you'll never get 12.200 V.  I
wonder if I calculated something wrong.)

There remains the problem of correctly labeling the inputs.  Labelling
the fan and temperature sensors is quite straightforward. On the other
hand, labelling +5V and +12V correctly is quite tricky, since both in0
and in1 have raw values that look okay for both +5V and +12V.  At this
point I've rebooted my machine and took note of my own data:

<a class="picture" href="/images/2013-12-bios-full.jpg" title="Full size image"><img src="/images/2013-12-bios.jpg" alt="Picture of my BIOS"> M5A97 PRO BIOS screenshot showing sensor data.</a>

Then I've booted into my Arch Linux and took the raw data from
lm-sensors (pasted at the end of this post). Based on it, I
guesstimated that in0 corresponds to +12V while in1 corresponds to
+5V.  This leaves us with in4 to in6, which I chose to just ignore.

And, finally, it seems that <a
href="https://www.kernel.org/doc/Documentation/hwmon/k10temp">the
temperature reported by the k10temp isn't real temperature</a>,
instead it's a relative temperature.  It's mostly meaningless since
the motherboard correctly informs the CPU temperature.

The end result is:

```python
$ sensors
k10temp-pci-00c3
Adapter: PCI adapter
CPU Temp (rel):  +26.5°C  (high = +70.0°C)
                          (crit = +90.0°C, hyst = +85.0°C)

it8721-isa-0290
Adapter: ISA adapter
+12V:        +12.10 V  (min =  +2.68 V, max =  +2.68 V)  ALARM
+5V:          +5.03 V  (min =  +0.11 V, max =  +0.60 V)  ALARM
Vcore:        +1.45 V  (min =  +1.66 V, max =  +1.12 V)  ALARM
+3.3V:        +3.29 V  (min =  +4.68 V, max =  +0.79 V)  ALARM
3VSB:         +0.77 V  (min =  +0.24 V, max =  +4.13 V)
Vbat:         +3.36 V
CPU Fan:     1290 RPM  (min =  105 RPM)
Chassis Fan: 1192 RPM  (min =   16 RPM)
Power Fan:      0 RPM  (min =   10 RPM)  ALARM
CPU Temp:     +37.0°C  (low  = +72.0°C, high = +10.0°C)  ALARM  sensor = thermistor
M/B Temp:     +30.0°C  (low  = +15.0°C, high = -128.0°C)  ALARM  sensor = thermistor
```

Much better!  If you have the same motherboard as mine and want to
have this beauty, just drop this snippet in your `/etc/sensors.conf`:

```bash
# Asus M5A97 PRO
chip "k10temp-pci-00c3"
     label temp1 "CPU Temp (rel)"

chip "it8721-*"
     label  in0 "+12V"
     label  in1 "+5V"
     label  in2 "Vcore"
     ignore in4
     ignore in5
     ignore in6

     compute in0  @ * (515/120), @ / (515/120)
     compute in1  @ * (215/120), @ / (215/120)

     ignore temp3
     label temp1 "CPU Temp"
     label temp2 "M/B Temp"

     label fan1 "CPU Fan"
     label fan2 "Chassis Fan"
     label fan3 "Power Fan"

     ignore intrusion0
```

I've posted the configuration snippet above to the lm-sensors mailing
list as well, so hopefully it will make it to the <a
href="http://www.lm-sensors.org/wiki/Configurations">lm-sensors
configurations wiki page</a> :).

<hr>

The raw data after the BIOS screenshot:

```python
k10temp-pci-00c3
Adapter: PCI adapter
temp1:
  temp1_input: 30.125
  temp1_max: 70.000
  temp1_crit: 90.000
  temp1_crit_hyst: 85.000

it8721-isa-0290
Adapter: ISA adapter
in0:
  in0_input: 2.820
  in0_min: 0.624
  in0_max: 0.624
  in0_alarm: 1.000
in1:
  in1_input: 2.808
  in1_min: 0.060
  in1_max: 0.336
  in1_alarm: 1.000
in2:
  in2_input: 1.452
  in2_min: 1.656
  in2_max: 1.116
  in2_alarm: 1.000
+3.3V:
  in3_input: 3.288
  in3_min: 4.680
  in3_max: 0.792
  in3_alarm: 1.000
in4:
  in4_input: 0.612
  in4_min: 0.360
  in4_max: 0.936
  in4_alarm: 0.000
in5:
  in5_input: 2.520
  in5_min: 0.144
  in5_max: 1.008
  in5_alarm: 1.000
in6:
  in6_input: 0.000
  in6_min: 1.728
  in6_max: 0.108
  in6_alarm: 1.000
3VSB:
  in7_input: 0.768
  in7_min: 0.240
  in7_max: 4.128
  in7_alarm: 0.000
Vbat:
  in8_input: 3.360
fan1:
  fan1_input: 1454.000
  fan1_min: 105.000
  fan1_alarm: 0.000
fan2:
  fan2_input: 1194.000
  fan2_min: 16.000
  fan2_alarm: 0.000
fan3:
  fan3_input: 0.000
  fan3_min: 10.000
  fan3_alarm: 1.000
temp1:
  temp1_input: 40.000
  temp1_max: 10.000
  temp1_min: 72.000
  temp1_alarm: 1.000
  temp1_type: 4.000
  temp1_offset: 0.000
temp2:
  temp2_input: 30.000
  temp2_max: -128.000
  temp2_min: 15.000
  temp2_alarm: 1.000
  temp2_type: 4.000
  temp2_offset: 0.000
temp3:
  temp3_input: -128.000
  temp3_max: 49.000
  temp3_min: -55.000
  temp3_alarm: 0.000
  temp3_type: 0.000
  temp3_offset: 0.000
intrusion0:
  intrusion0_alarm: 0.000
```
