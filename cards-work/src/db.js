// db.js
import Dexie from 'dexie';

export const db = new Dexie('fixtures');
db.version(1).stores({
  fixtures: 'id,datetime,homeclub', // Primary key and indexed props
});