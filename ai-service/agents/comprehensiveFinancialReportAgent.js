// ai-service/agents/comprehensiveFinancialReportAgent.js

/**
 * Defines the structure for a comprehensive financial report.
 * This agent orchestrates other agents to build the report.
 * @returns {Promise<object>} A promise that resolves with the report structure.
 */
function generateComprehensiveFinancialReport() {
  return Promise.resolve({
    description: 'A comprehensive financial report including summary, risk analysis, and cash flow forecast.',
    dependsOn: [
      { agent: 'financialSummary', params: null },
      { agent: 'riskAnalysis', params: null },
      { agent: 'cashFlowForecast', params: null },
    ],
    processedBy: 'comprehensiveFinancialReportAgent',
  });
}

module.exports = { generateComprehensiveFinancialReport };
