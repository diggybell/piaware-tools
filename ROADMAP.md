# piaware-roadmap
Getting a piece of software running is only one small step. The core engine that processes data for PiAware Tools
is in pretty solid shape. The next steps are going to perhaps be more interesting as more data is integrated.

## First Step - Features For The Operator
These features will be valuable to the person operating the PiAware Tools platform.

* Extended Search
The search engine will provide multiple search dimensions to query aircraft and flight data.

* FAA Registry View
The FAA registry is a read-only copy of registered aircraft. This module will allow the user to
view all of the details that are available in the registry. It will also provide features to update
the data in the registry.

* ICAO Airport View
The ICAO airport registry is a read-only copy of all airports across the globe. This module will
allow the user to view all of the details that are contained in the registry. It will also provide features
to update the data in the registry.

## Second Step - Integrate DoD Registry
I have curated data from many different planespotters to create a registry of DoD aircraft. The registry was
constructed to mirror the structure used by the FAA in their registry. The details on each of the aircraft types
was gathered from public sources such as af.mil and Wikipedia. The tail number, ICAO mode-S hex code, branch of
service, assigned base, and unit were all gathered by planespotters.
* DoD Registry View
The DoD registry is a read-only copy of data capturing DoD aircraft. This module will allow the user to view all
of the details that are contained in the registry. Features to update this registry will be determined in the Fourth Step below.

## Third Step - Flight Profile and Track Map
The PiAware Tools engine constructs virtual flights/segments based on the ADS-B signals received from each
aircraft. After the flight has been completed the profiling engine will analyze the data and produce a profile
snapshot for the flight. After the profile is created the engine will create a flight track map that can be
displayed to show the path of the aircraft through the airspace seen by the PiAware receiver.

## Fourth Step - DoD Registry Contributions and Editing
