Readme

# Tread Wear and Rotation Tracker

## About

This application allows users to track their tire rotation history and gain insights into the average tread wear for each position.

Each user can add as many vehicles as they would like. (We should probably limit this to 5 per user and charge a fleet fee for more).

When a vehicle is added the user will choose whether to rotate 4 or 5 tires (some vehicles will rotate a spare). The user will then add details for each tire (a label, TIN [unique identifier], and the current tread depth).

Throughout use of the application, the user must always have one vehicle currently selected.  By default, the last selected vehicle will be selected (this is stored for each vehicle in the vehicles table and updated every time s vehicle is selected).

To ensure vehicles are set up properly, users will be forced to create a vehicle if none exists. This is accomplished through middleware.
(this could be changed to the routes file and per page instead of every page and excluding certain pages)

They then must add tires to the vehicle if there are no active tires for the currently selected vehicle.  The number of tires must match the number expected in the vehicle.  This is another middleware check.  When tires are added, a first rotation will be created, so mileage (odometer) and date will need to be entered.

When a user rotates their tires, they can drag and drop them around the vehicle manually or they can use a template, provided visually.  They will need to enter the tread depth, date (default as today) and odometer before saving.

Each rotation stores the tread depth at the time of the rotation.  Thus, the current record in the DB shows the current position and the tread depth at the time of rotation. This depth also represents the ending depth of the prior position.  The difference between the depth at the start and end of each rotation is the tread wear and a key data point in the application.

The user can then view a report for each tire and position.

When a user buys new tires, the can retire the prior tires (but the tread wear is still part of the history).

A user can also have multiple sets of tires, such as winter snow tires and summer tires, so tire status can be installed, removed, and retired.  Users can swap a set of tires for another.

Finally, a user can soft delete a vehicle. Currently no option to restore.

## Installation and Deployments

*Run all scripts from the root directory*

### New Local Development

#### `./bin/init.sh`

Start a new environment with a new .env, composer, app key, migrations, and npm


### Updating the app after merging source

#### `./bin/update.sh`

Install composer, npm, and run migrations.

### Before Committing

#### `./bin/precomit.sh`

Run pint and pest

### Production Deployment

#### `./bin/deploy-production.sh`

Deploys on the server.  

## Dependencies

- blade-phosphor-icons 
- laravel/breeze
- livewire/livewire 3
- livewire/volt
- staudenmeir/belongs-to-through 

### Dev Dependencies

- laravel/pint
- pestphp/pest
