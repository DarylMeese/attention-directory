'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname, 'assets/push.js'), 'utf8');
let count = 0;
function setup({ secure = true, supported = true, sdk = true } = {}) {
    let click, timer, command;
    const status = { textContent: '' };
    const button = { disabled: false, parentElement: { querySelector: () => status } };
    const window = {
        isSecureContext: secure,
        setTimeout: callback => { timer = callback; return 1; },
        clearTimeout: () => { timer = null; },
        ...(supported ? { Notification: {} } : {}),
        ...(sdk ? { Gravitec: { push: value => { command = value; } } } : {}),
    };
    vm.runInNewContext(source, {
        window, navigator: supported ? { serviceWorker: {} } : {},
        Notification: { permission: 'granted' },
        document: { addEventListener: (event, listener) => { click = listener; } },
    });
    return { button, status, click: () => click({ target: { closest: () => button } }), malformedClick: () => click({ target: {} }), command: () => command, timeout: () => timer() };
}
let test = setup({ secure: false }); test.click(); assert.match(test.status.textContent, /not available/); count++;
test = setup({ supported: false }); test.click(); assert.match(test.status.textContent, /not available/); count++;
test = setup({ sdk: false }); test.click(); assert.match(test.status.textContent, /still loading/); count++;
test = setup(); test.click(); assert.equal(test.command()[0], 'registerUserForPush'); count++;
assert.equal(test.button.disabled, true); count++;
test.command()[1](); assert.equal(test.button.disabled, false); assert.match(test.status.textContent, /allowed in this browser/); count++;
test = setup(); test.click(); test.command()[2](); assert.equal(test.button.disabled, false); assert.match(test.status.textContent, /could not be enabled/); count++;
test = setup(); test.click(); test.timeout(); assert.equal(test.button.disabled, false); assert.match(test.status.textContent, /check your browser/); count++;
test = setup(); test.malformedClick(); assert.equal(test.command(), undefined); count++;
for (const match of fs.readFileSync(require('node:path').join(__dirname, 'src/settings.php'), 'utf8').matchAll(/pattern="([^"]+)"/g)) {
    assert(new RegExp(`^(?:${match[1]})$`, 'v').test('valid-test-id'));
    count++;
}
console.log(`PASS: ${count} notification UI assertions; no provider calls or permission prompts.`);
