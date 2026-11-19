import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import { splitSummary } from '../src/core/text.js'

// Notes and tasks are HTML, written in the desktop's editor, so what the app saves has to be markup or a typed line
// break is lost. The Tasks screen reloads when a task is made anywhere, and the note's age is a translated string.

const views = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '../src/views' )
const read = name => readFileSync( path.join( views, name ), 'utf8' )

test( 'a note and a task are saved as HTML', () => {
  assert.match( read( 'panels.jsx' ), /content: plainToHtml\( text \)/ )
  assert.match( read( 'tasks.jsx' ), /content: plainToHtml\( details \)/ )
} )

test( 'a task made anywhere reloads the task lists', () => {
  assert.match( read( 'tasks.jsx' ), /ctx\.emit\( 'task:created'/ )
  assert.match( read( 'tasks.jsx' ), /ctx\.on\( 'task:created', reload \)/ )
} )

test( 'a note says how long ago it was with a translated string', () => {
  const panels = read( 'panels.jsx' )

  assert.match( panels, /sprintf\( __\( '%s ago' \), n\.i18n\.time_diff \)/ )
  assert.doesNotMatch( panels, /time_diff } ago/ )
} )

test( 'older activity says which entries of the cursor second it already has', () => {
  assert.match( read( 'panels.jsx' ), /exclude: have\.join\( ',' \)/ )
} )

test( 'a new task takes its summary from the first line', () => {
  assert.deepEqual( splitSummary( 'Call back' ), { summary: 'Call back', content: '' } )
  assert.deepEqual( splitSummary( '  Call back  \n\nAsk about the quote\nand the date\n' ), { summary: 'Call back', content: 'Ask about the quote\nand the date' } )
  assert.deepEqual( splitSummary( 'a\r\nb' ), { summary: 'a', content: 'b' } )
} )

test( 'a first line too long for a title is cut for the summary and kept whole in the details', () => {
  const long = 'word '.repeat( 40 ).trim()
  const { summary, content } = splitSummary( long + '\nmore' )

  assert.ok( summary.length <= 120 )
  assert.ok( summary.endsWith( '…' ) )
  assert.equal( content, long + '\nmore' )
} )

test( 'completing a task offers a note, and a new task can be assigned by whoever can edit others tasks', () => {
  const tasks = read( 'tasks.jsx' )

  assert.match( tasks, /label: __\( 'Add note' \)/ )
  assert.match( tasks, /activity: 1, type: 'task_complete'/ )
  assert.match( tasks, /ctx\.can\( 'edit_others_tasks' \)/ )
  assert.match( tasks, /user_id: assignee/ )
} )
