// ai-service/agent-worker.js

const { parentPort, workerData } = require('worker_threads');
const path = require('path');

/**
 * A map of available agents.
 * The key is the agent name, and the value is the path to the agent's module and the function to call.
 */
const agents = {
  financialSummary: {
    path: './agents/financialSummaryAgent.js',
    function: 'generateFinancialSummary',
  },
  riskAnalysis: {
    path: './agents/riskAnalysisAgent.js',
    function: 'generateRiskAnalysis',
  },
  cashFlowForecast: {
    path: './agents/cashFlowForecastAgent.js',
    function: 'generateCashFlowForecast',
  },
  comprehensiveFinancialReport: {
    path: './agents/comprehensiveFinancialReportAgent.js',
    function: 'generateComprehensiveFinancialReport',
  },
};

/**
 * Runs the specified agent.
 * @param {string} agentName The name of the agent to run.
 * @param {any} data The data to pass to the agent.
 */
async function runAgent(agentName, data) {
  const agentInfo = agents[agentName];

  if (!agentInfo) {
    throw new Error(`Agent '${agentName}' not found.`);
  }

  try {
    const agentModule = require(agentInfo.path);
    const agentFunction = agentModule[agentInfo.function];

    if (typeof agentFunction !== 'function') {
      throw new Error(`Agent function '${agentInfo.function}' not found in '${agentInfo.path}'.`);
    }

    const result = await agentFunction(data);
    parentPort.postMessage({ status: 'completed', result });
  } catch (error) {
    parentPort.postMessage({ status: 'error', error: error.message });
  }
}

// Listen for messages from the main thread
parentPort.on('message', ({ agentName, data }) => {
  runAgent(agentName, data).finally(() => {
    // Ensure the worker exits after processing the message
    process.exit();
  });
});
