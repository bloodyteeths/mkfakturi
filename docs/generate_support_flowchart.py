#!/usr/bin/env python3
"""
Facturino Support Flowchart Generator

This script generates a visual flowchart of the support process for Facturino Macedonia.
Requires: graphviz, matplotlib, or similar visualization library

Usage:
    python generate_support_flowchart.py

Output:
    support_flowchart.png - Visual representation of the support process
"""

import os
try:
    from graphviz import Digraph
    HAS_GRAPHVIZ = True
except ImportError:
    HAS_GRAPHVIZ = False
    print("Warning: graphviz not available. Install with: pip install graphviz")

def create_support_flowchart():
    """Create a visual support flowchart using Graphviz"""
    
    if not HAS_GRAPHVIZ:
        print("Creating text-based flowchart representation...")
        create_text_flowchart()
        return
    
    # Create a new directed graph
    dot = Digraph(comment='Facturino Support Process')
    dot.attr(rankdir='TB', size='16,20')
    dot.attr('node', shape='box', style='rounded,filled', fontname='Arial')
    
    # Define color scheme
    colors = {
        'start': '#e3f2fd',      # Light blue
        'channel': '#fff3e0',    # Light orange  
        'decision': '#f3e5f5',   # Light purple
        'team': '#e8f5e8',       # Light green
        'resolution': '#fff9c4', # Light yellow
        'escalation': '#ffebee'  # Light red
    }
    
    # Start node
    dot.node('A', 'User Reports Issue', fillcolor=colors['start'])
    
    # Support channels
    dot.node('B', 'Support Channel?', shape='diamond', fillcolor=colors['decision'])
    dot.node('C', 'In-App Chat Widget', fillcolor=colors['channel'])
    dot.node('D', 'Email Support', fillcolor=colors['channel'])
    dot.node('E', 'Phone Support', fillcolor=colors['channel'])
    dot.node('F', 'Partner Portal', fillcolor=colors['channel'])
    
    # Initial routing
    dot.node('G', 'Level 1 Support Agent', fillcolor=colors['team'])
    dot.node('H', 'Partner Success Manager', fillcolor=colors['team'])
    
    # Issue categorization
    dot.node('I', 'Issue Type?', shape='diamond', fillcolor=colors['decision'])
    dot.node('J', 'Technical Issue', fillcolor=colors['team'])
    dot.node('K', 'Account/Billing', fillcolor=colors['team'])
    dot.node('L', 'Migration Support', fillcolor=colors['team'])
    dot.node('M', 'Tax Compliance', fillcolor=colors['team'])
    dot.node('N', 'Banking Integration', fillcolor=colors['team'])
    
    # Severity assessment
    dot.node('O', 'Severity Level?', shape='diamond', fillcolor=colors['decision'])
    dot.node('P', 'Billing Team', fillcolor=colors['team'])
    dot.node('Q', 'Migration Specialist', fillcolor=colors['team'])
    dot.node('R', 'Tax Compliance Expert', fillcolor=colors['team'])
    dot.node('S', 'Banking Team', fillcolor=colors['team'])
    
    # Technical escalation
    dot.node('T', 'Critical/High Priority', fillcolor=colors['escalation'])
    dot.node('U', 'Medium/Low Priority', fillcolor=colors['team'])
    dot.node('V', 'Development Team', fillcolor=colors['escalation'])
    dot.node('W', 'Technical Support L2', fillcolor=colors['team'])
    
    # Resolution process
    dot.node('X', 'Issue Resolved?', shape='diamond', fillcolor=colors['decision'])
    dot.node('Y', 'Close Ticket', fillcolor=colors['resolution'])
    dot.node('Z', 'Escalate Further', fillcolor=colors['escalation'])
    
    # High-level escalation
    dot.node('AA', 'Senior Technical Lead', fillcolor=colors['escalation'])
    dot.node('BB', 'Engineering Manager', fillcolor=colors['escalation'])
    dot.node('CC', 'Product Owner', fillcolor=colors['escalation'])
    
    # Follow-up process
    dot.node('DD', 'Send Resolution Email', fillcolor=colors['resolution'])
    dot.node('EE', 'Request Feedback', fillcolor=colors['resolution'])
    dot.node('FF', 'Update Knowledge Base', fillcolor=colors['resolution'])
    
    # Product roadmap
    dot.node('GG', 'Schedule Fix/Enhancement', fillcolor=colors['escalation'])
    dot.node('HH', 'Notify Customer', fillcolor=colors['resolution'])
    dot.node('II', 'Track in Roadmap', fillcolor=colors['escalation'])
    
    # Define edges (connections)
    edges = [
        ('A', 'B'),
        ('B', 'C'), ('B', 'D'), ('B', 'E'), ('B', 'F'),
        ('C', 'G'), ('D', 'G'), ('E', 'G'),
        ('F', 'H'),
        ('G', 'I'), ('H', 'I'),
        ('I', 'J'), ('I', 'K'), ('I', 'L'), ('I', 'M'), ('I', 'N'),
        ('J', 'O'),
        ('K', 'P'), ('L', 'Q'), ('M', 'R'), ('N', 'S'),
        ('O', 'T'), ('O', 'U'),
        ('T', 'V'), ('U', 'W'),
        ('P', 'X'), ('Q', 'X'), ('R', 'X'), ('S', 'X'), ('V', 'X'), ('W', 'X'),
        ('X', 'Y'), ('X', 'Z'),
        ('Z', 'AA'),
        ('AA', 'BB'),
        ('BB', 'CC'),
        ('Y', 'DD'),
        ('DD', 'EE'),
        ('EE', 'FF'),
        ('CC', 'GG'),
        ('GG', 'HH'),
        ('HH', 'II')
    ]
    
    # Add edges to graph
    for edge in edges:
        dot.edge(edge[0], edge[1])
    
    # Add edge labels for decision points
    dot.edge('B', 'C', label='Chat')
    dot.edge('B', 'D', label='Email')
    dot.edge('B', 'E', label='Phone')
    dot.edge('B', 'F', label='Partner')
    
    dot.edge('I', 'J', label='Technical')
    dot.edge('I', 'K', label='Billing')
    dot.edge('I', 'L', label='Migration')
    dot.edge('I', 'M', label='Tax')
    dot.edge('I', 'N', label='Banking')
    
    dot.edge('O', 'T', label='High/Critical')
    dot.edge('O', 'U', label='Medium/Low')
    
    dot.edge('X', 'Y', label='Yes')
    dot.edge('X', 'Z', label='No')
    
    # Render the graph
    try:
        output_path = 'support_flowchart'
        dot.render(output_path, format='png', cleanup=True)
        print(f"Flowchart saved as {output_path}.png")
        return True
    except Exception as e:
        print(f"Error generating flowchart: {e}")
        create_text_flowchart()
        return False

def create_text_flowchart():
    """Create a text-based flowchart representation"""
    
    flowchart_text = """
FACTURINO SUPPORT PROCESS FLOWCHART (Text Version)
================================================

1. USER REPORTS ISSUE
   ↓
2. SUPPORT CHANNEL SELECTION
   ├─ In-App Chat Widget ─────────┐
   ├─ Email: support@facturino.mk ┤
   ├─ Phone: +389 2 XXX-XXXX ─────┤
   └─ Partner Portal ─────────────┼─ Partner Success Manager
                                  │
3. LEVEL 1 SUPPORT AGENT ←───────┘
   ↓
4. ISSUE TYPE CLASSIFICATION
   ├─ Technical Issue ────────────┐
   ├─ Account/Billing ───────────┤
   ├─ Migration Support ─────────┤
   ├─ Tax Compliance ────────────┤
   └─ Banking Integration ───────┤
                                 │
5. TEAM ROUTING                  │
   ├─ Development Team ←─────────┼─ Critical/High Priority
   ├─ Technical Support L2 ←─────┼─ Medium/Low Priority  
   ├─ Billing Team ←─────────────┼─ Account Issues
   ├─ Migration Specialist ←─────┼─ Import/Export Issues
   ├─ Tax Compliance Expert ←────┼─ ДДВ-04, UBL XML Issues
   └─ Banking Integration Team ←─┼─ PSD2, Sync Issues
                                 │
6. RESOLUTION ATTEMPT ←──────────┘
   ↓
7. ISSUE RESOLVED?
   ├─ YES → Close Ticket → Send Resolution Email → Request Feedback → Update KB
   └─ NO → ESCALATION PROCESS
           ↓
           Senior Technical Lead
           ↓
           Engineering Manager  
           ↓
           Product Owner
           ↓
           Schedule Fix/Enhancement → Notify Customer → Track in Roadmap

PRIORITY LEVELS:
🔴 Critical (P1): Platform down, data loss, security breach
🟡 High (P2): Feature failures, banking sync issues
🟢 Medium (P3): Single user issues, minor UI problems
⚪ Low (P4): Feature requests, enhancements

RESPONSE TIMES:
- Critical: 1 hour
- High: 4 hours  
- Medium: 24 hours
- Low: 48 hours

SUPPORT CHANNELS:
📧 support@facturino.mk
📞 +389 2 XXX-XXXX
💬 In-app chat widget
🤝 partners.facturino.mk/support
🚨 Emergency: +389 70 XXX-XXX
"""
    
    # Save text version
    with open('support_flowchart.txt', 'w', encoding='utf-8') as f:
        f.write(flowchart_text)
    
    print("Text-based flowchart saved as support_flowchart.txt")
    print("\nTo generate a visual PNG flowchart, install graphviz:")
    print("pip install graphviz")
    print("Then run this script again.")

def create_mermaid_flowchart():
    """Create Mermaid.js flowchart syntax for web rendering"""
    
    mermaid_code = """
```mermaid
flowchart TD
    A[User Reports Issue] --> B{Support Channel?}
    
    B --> C[In-App Chat Widget]
    B --> D[Email: support@facturino.mk]  
    B --> E[Phone: +389 2 XXX-XXXX]
    B --> F[Partner Portal Ticket]
    
    C --> G[Level 1 Support Agent]
    D --> G
    E --> G
    F --> H[Partner Success Manager]
    
    G --> I{Issue Type?}
    H --> I
    
    I --> J[Technical Issue]
    I --> K[Account/Billing]
    I --> L[Migration Support]
    I --> M[Tax Compliance]
    I --> N[Banking Integration]
    
    J --> O{Severity Level?}
    K --> P[Billing Team]
    L --> Q[Migration Specialist]
    M --> R[Tax Compliance Expert]
    N --> S[Banking Integration Team]
    
    O --> T[Critical/High]
    O --> U[Medium/Low]
    
    T --> V[Development Team]
    U --> W[Technical Support L2]
    
    P --> X{Resolved?}
    Q --> X
    R --> X
    S --> X
    V --> X
    W --> X
    
    X --> Y[Yes - Close Ticket]
    X --> Z[No - Escalate]
    
    Z --> AA[Senior Technical Lead]
    AA --> BB[Engineering Manager]
    BB --> CC[Product Owner]
    
    Y --> DD[Send Resolution Email]
    DD --> EE[Request Feedback]
    EE --> FF[Update Knowledge Base]
    
    CC --> GG[Schedule Fix/Enhancement]
    GG --> HH[Notify Customer]
    HH --> II[Track in Roadmap]
    
    classDef startNode fill:#e3f2fd
    classDef channelNode fill:#fff3e0
    classDef decisionNode fill:#f3e5f5
    classDef teamNode fill:#e8f5e8
    classDef resolutionNode fill:#fff9c4
    classDef escalationNode fill:#ffebee
    
    class A startNode
    class C,D,E,F channelNode
    class B,I,O,X decisionNode
    class G,H,J,K,L,M,N,P,Q,R,S,U,W teamNode
    class Y,DD,EE,FF,HH resolutionNode
    class T,V,Z,AA,BB,CC,GG,II escalationNode
```
"""
    
    with open('support_flowchart_mermaid.md', 'w', encoding='utf-8') as f:
        f.write("# Facturino Support Process Flowchart (Mermaid)\n\n")
        f.write(mermaid_code)
    
    print("Mermaid flowchart saved as support_flowchart_mermaid.md")
    print("This can be rendered on GitHub, GitLab, or any Mermaid-compatible platform")

if __name__ == "__main__":
    print("Generating Facturino Support Process Flowchart...")
    print("=" * 50)
    
    # Try to create visual flowchart
    success = create_support_flowchart()
    
    # Also create text and Mermaid versions
    create_text_flowchart()
    create_mermaid_flowchart()
    
    print("\nFlowchart generation complete!")
    print("\nGenerated files:")
    print("- SupportFlowchart.md (comprehensive documentation)")
    if success:
        print("- support_flowchart.png (visual flowchart)")
    print("- support_flowchart.txt (text version)")
    print("- support_flowchart_mermaid.md (web-renderable version)")

