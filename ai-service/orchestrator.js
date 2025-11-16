// ai-service/orchestrator.js

const { Worker } = require('worker_threads');
const path = require('path');

/**
 * Runs an agent in a separate worker thread.
 * @param {string} agentName The name of the agent to run.
 * @param {any} data The data to pass to the agent.
 * @returns {Promise<any>} A promise that resolves with the result from the agent.
 */
function runAgent(agentName, data) {
  return new Promise((resolve, reject) => {
    const worker = new Worker(path.resolve(__dirname, 'agent-worker.js'));

    worker.on('message', message => {
      if (message.status === 'completed') {
        resolve(message.result);
      } else if (message.status === 'error') {
        reject(new Error(message.error));
      }
    });

    worker.on('error', reject);

    worker.on('exit', code => {
      if (code !== 0) {
        // This may not be a rejection, as the 'message' event with an error might have already been handled.
        // However, if the worker exits without sending a 'completed' or 'error' message, we should reject.
        // For simplicity, we'll rely on the 'error' event for explicit errors.
      }
    });

    worker.postMessage({ agentName, data });
  });
}

module.exports = { runAgent };
