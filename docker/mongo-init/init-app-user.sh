#!/bin/bash
set -e

mongosh --quiet \
  -u "$MONGO_INITDB_ROOT_USERNAME" \
  -p "$MONGO_INITDB_ROOT_PASSWORD" \
  --authenticationDatabase admin \
  --eval '
    db = db.getSiblingDB("miniticket");

    db.createRole({
      role: "ticketEventsAccess",
      privileges: [
        {
          resource: {
            db: "miniticket",
            collection: "ticket_events"
          },
          actions: ["find", "insert"]
        }
      ],
      roles: []
    });

    db.createUser({
      user: process.env.MONGO_APP_USER,
      pwd: process.env.MONGO_APP_PASSWORD,
      roles: [
        {
          role: "ticketEventsAccess",
          db: "miniticket"
        }
      ]
    });
  '