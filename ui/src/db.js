// db.js
import Dexie from 'dexie';

export const db = new Dexie('fixturesDatabase');
db.version(1).stores({
  fixtures: 'id,datetimeZ,homeclub', // Primary key and indexed props
});