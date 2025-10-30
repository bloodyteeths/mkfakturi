const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const { WebSocketServer } = require('ws');
const http = require('http');

const app = express();
const server = http.createServer(app);
const wss = new WebSocketServer({ server, port: process.env.MCP_PORT || 3002 });

// Security middleware
app.use(helmet());
app.use(cors({
  origin: process.env.CORS_ORIGIN || 'http://localhost:8000',
  credentials: true
}));
app.use(morgan('combined'));
app.use(express.json());

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'ai-mcp',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    memory: process.memoryUsage(),
    environment: process.env.NODE_ENV
  });
});

// AI Financial Analysis endpoints
app.get('/api/financial-summary', async (req, res) => {
  try {
    // Mock financial summary for Macedonia market
    const summary = {
      totalRevenue: 125000,
      totalExpenses: 85000,
      netProfit: 40000,
      invoicesCount: 156,
      paymentsCount: 142,
      averageInvoiceValue: 801.28,
      currency: 'MKD',
      period: 'last_30_days',
      insights: [
        'Revenue increased 12% compared to last month',
        'Payment collection time improved by 2.3 days',
        '94% invoice payment rate - excellent performance'
      ],
      riskScore: 0.15,
      riskLevel: 'low'
    };
    
    res.json(summary);
  } catch (error) {
    res.status(500).json({ error: 'Failed to generate financial summary' });
  }
});

app.get('/api/risk-analysis', async (req, res) => {
  try {
    // Mock risk analysis for Macedonia business
    const riskAnalysis = {
      overallRisk: 0.15,
      riskLevel: 'low',
      factors: [
        {
          category: 'cash_flow',
          score: 0.12,
          description: 'Strong cash flow patterns',
          impact: 'positive'
        },
        {
          category: 'customer_concentration',
          score: 0.25,
          description: 'Top 3 customers represent 45% of revenue',
          impact: 'moderate'
        },
        {
          category: 'payment_delays',
          score: 0.08,
          description: 'Low payment delay frequency',
          impact: 'positive'
        }
      ],
      recommendations: [
        'Continue monitoring customer concentration',
        'Consider payment terms adjustment for large customers',
        'Maintain current collection processes'
      ],
      lastUpdated: new Date().toISOString()
    };
    
    res.json(riskAnalysis);
  } catch (error) {
    res.status(500).json({ error: 'Failed to generate risk analysis' });
  }
});

app.get('/api/cash-flow-forecast', async (req, res) => {
  try {
    // Mock cash flow forecast for Macedonia business
    const forecast = {
      currency: 'MKD',
      period: 'next_90_days',
      projections: [
        { date: '2025-08-01', inflow: 45000, outflow: 28000, net: 17000 },
        { date: '2025-08-15', inflow: 52000, outflow: 31000, net: 21000 },
        { date: '2025-09-01', inflow: 48000, outflow: 29000, net: 19000 },
        { date: '2025-09-15', inflow: 55000, outflow: 33000, net: 22000 },
        { date: '2025-10-01', inflow: 51000, outflow: 30000, net: 21000 },
        { date: '2025-10-15', inflow: 58000, outflow: 35000, net: 23000 }
      ],
      confidence: 0.78,
      trends: {
        revenue_growth: 0.08,
        seasonal_factor: 1.12,
        payment_velocity: 28.5
      },
      alerts: []
    };
    
    res.json(forecast);
  } catch (error) {
    res.status(500).json({ error: 'Failed to generate cash flow forecast' });
  }
});

// MCP WebSocket for real-time communication
wss.on('connection', (ws) => {
  console.log('MCP client connected');
  
  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      
      // Handle different MCP message types
      switch (data.type) {
        case 'financial_insight_request':
          ws.send(JSON.stringify({
            type: 'financial_insight_response',
            data: {
              insight: 'Based on recent patterns, consider offering 2% early payment discount',
              confidence: 0.82,
              impact: 'moderate_positive'
            }
          }));
          break;
          
        case 'risk_alert_subscribe':
          // Set up risk monitoring
          ws.send(JSON.stringify({
            type: 'subscription_confirmed',
            data: { subscription: 'risk_alerts' }
          }));
          break;
          
        default:
          ws.send(JSON.stringify({
            type: 'error',
            data: { message: 'Unknown message type' }
          }));
      }
    } catch (error) {
      ws.send(JSON.stringify({
        type: 'error',
        data: { message: 'Invalid message format' }
      }));
    }
  });
  
  ws.on('close', () => {
    console.log('MCP client disconnected');
  });
});

// Start the server
const port = process.env.PORT || 3001;
server.listen(port, () => {
  console.log(`AI-MCP Financial Assistant running on port ${port}`);
  console.log(`WebSocket MCP endpoint on port ${process.env.MCP_PORT || 3002}`);
  console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('Received SIGTERM, shutting down gracefully');
  server.close(() => {
    process.exit(0);
  });
});

