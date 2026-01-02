import { Component, ChangeDetectionStrategy, inject, signal, effect, ElementRef, ViewChild, AfterViewInit, computed } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import * as d3 from 'd3';
import { DataService } from '../../services/data.service';
import { ThemeService } from '../../services/theme.service';
import { DateRangePreset, ForecastData } from '../../models/data.models';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, CurrencyPipe],
  templateUrl: './dashboard.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements AfterViewInit {
  dataService = inject(DataService);
  themeService = inject(ThemeService);

  @ViewChild('salesTrendChart') private salesTrendChartEl!: ElementRef;
  @ViewChild('salesBySalesmanChart') private salesBySalesmanChartEl!: ElementRef;
  @ViewChild('salesByProjectChart') private salesByProjectChartEl!: ElementRef;
  @ViewChild('plotInventoryChart') private plotInventoryChartEl!: ElementRef;

  activeFilter = this.dataService.activeFilter;
  salesmanChartMetric = signal<'value' | 'unit'>('value');
  salesTrendChartMetric = signal<'value' | 'unit'>('value');
  salesByProjectChartMetric = signal<'value' | 'unit'>('value');
  plotInventoryChartMetric = signal<'value' | 'unit'>('value');
  
  // Urgent Billings State
  urgentBillings = this.dataService.urgentBillings;
  isOffCanvasOpen = signal(false);

  // AI Forecasting State
  showForecast = signal(false);
  forecastData = this.dataService.salesForecast;
  forecastLoading = this.dataService.forecastLoading;
  forecastError = this.dataService.forecastError;
  forecastDescription = this.dataService.forecastDescription;
  
  isForecastDisabled = computed(() => {
    const activeFilter = this.dataService.activeFilter();
    const hasEnoughData = this.dataService.historicalSalesMonthsCount() >= 12;
    const isFilterAllowed = activeFilter === 'this_year' || activeFilter === 'all_time';
    return !hasEnoughData || !isFilterAllowed;
  });

  forecastToggleTitle = computed(() => {
    if (this.dataService.historicalSalesMonthsCount() < 12) {
      return 'Diperlukan data penjualan selama periode >1 tahun';
    }
    const activeFilter = this.dataService.activeFilter();
    if (activeFilter !== 'this_year' && activeFilter !== 'all_time') {
      return 'Fitur ini hanya bisa di tab Tahun Ini';
    }
    return ''; // No title needed if enabled
  });

  chartsLoading = signal(true);
  private chartsInitialized = 0;
  private readonly totalCharts = 4;
  private isFullyInitialized = signal(false);

  // Color scale for inventory chart
  private projectColors = new Map<string, string>();
  private colorScale = d3.scaleOrdinal(d3.schemeTableau10);
  
  getProjectColor(projectName: string): string {
      if (!this.projectColors.has(projectName)) {
          this.projectColors.set(projectName, this.colorScale(projectName));
      }
      return this.projectColors.get(projectName)!;
  }

  get dashboardData() {
    return this.dataService.dashboardData;
  }

  private isViewInitialized = false;

  constructor() {
    effect(() => {
        // Auto-disable forecast if conditions are not met
        if (this.isForecastDisabled() && this.showForecast()) {
            this.showForecast.set(false);
            // Manually trigger the change event logic to clear data
            this.dataService.salesForecast.set(null);
            this.dataService.forecastError.set(null);
            this.dataService.forecastLoading.set(false);
            this.dataService.forecastDescription.set(null);
        }
    }, { allowSignalWrites: true });

    // This effect re-triggers the forecast when the filter changes while the forecast is active.
    effect(() => {
        const activeFilter = this.dataService.activeFilter();
        // Run only if the forecast toggle is on and it's not disabled for the current state.
        if (this.showForecast() && !this.isForecastDisabled()) {
            this.dataService.triggerForecast(activeFilter);
        }
    }, { allowSignalWrites: true });

    effect(() => {
      if (!this.isFullyInitialized()) {
        return;
      }

      const data = this.dashboardData();
      if (data) {
        const trendMetric = this.salesTrendChartMetric();
        const forecast = this.forecastData();
        const showFc = this.showForecast();
        this.drawSalesTrendChart(data.current.salesTrend, trendMetric, showFc ? forecast : null);
        
        const salesmanMetric = this.salesmanChartMetric();
        const salesmanChartData = salesmanMetric === 'value' 
            ? data.current.salesBySalesman.byValue 
            : data.current.salesBySalesman.byUnit;
        this.drawSalesBySalesmanChart(salesmanChartData, salesmanMetric);

        const projectMetric = this.salesByProjectChartMetric();
        const projectChartData = projectMetric === 'value'
            ? data.current.salesByProject.byValue
            : data.current.salesByProject.byUnit;
        this.drawSalesByProjectChart(projectChartData, projectMetric);

        const inventoryMetric = this.plotInventoryChartMetric();
        const inventoryChartData = inventoryMetric === 'value'
            ? data.plotInventoryByProject.byValue
            : data.plotInventoryByProject.byUnit;
        this.drawPlotInventoryChart(inventoryChartData, inventoryMetric);
      }
    });
  }

  ngAfterViewInit() {
    this.isViewInitialized = true;
    setTimeout(() => this.setupChartObservers(), 0);
  }

  private setupChartObservers(): void {
    this.observeChart(this.salesTrendChartEl, () => {
        const data = this.dashboardData();
        if (data) this.drawSalesTrendChart(data.current.salesTrend, this.salesTrendChartMetric(), null);
    });

    this.observeChart(this.salesBySalesmanChartEl, () => {
        const data = this.dashboardData();
        if (data) {
            const salesmanMetric = this.salesmanChartMetric();
            const salesmanChartData = salesmanMetric === 'value' 
                ? data.current.salesBySalesman.byValue 
                : data.current.salesBySalesman.byUnit;
            this.drawSalesBySalesmanChart(salesmanChartData, salesmanMetric);
        }
    });
    
    this.observeChart(this.salesByProjectChartEl, () => {
        const data = this.dashboardData();
        if (data) {
            const projectMetric = this.salesByProjectChartMetric();
            const projectChartData = projectMetric === 'value'
                ? data.current.salesByProject.byValue
                : data.current.salesByProject.byUnit;
            this.drawSalesByProjectChart(projectChartData, projectMetric);
        }
    });

    this.observeChart(this.plotInventoryChartEl, () => {
        const data = this.dashboardData();
        if (data) {
            const inventoryMetric = this.plotInventoryChartMetric();
            const inventoryChartData = inventoryMetric === 'value'
                ? data.plotInventoryByProject.byValue
                : data.plotInventoryByProject.byUnit;
            this.drawPlotInventoryChart(inventoryChartData, inventoryMetric);
        }
    });
  }

  private observeChart(elementRef: ElementRef, drawFn: () => void): void {
      if (!elementRef?.nativeElement) {
          console.warn('Chart element not found for observer.');
          this.chartsInitialized++;
          if (this.chartsInitialized >= this.totalCharts) {
              this.chartsLoading.set(false);
              this.isFullyInitialized.set(true);
          }
          return;
      }
      const element = elementRef.nativeElement;
      const observer = new ResizeObserver(entries => {
          if (entries[0].contentRect.width > 0) {
              drawFn();
              this.chartsInitialized++;
              if (this.chartsInitialized >= this.totalCharts) {
                  this.chartsLoading.set(false);
                  this.isFullyInitialized.set(true);
              }
              observer.disconnect();
          }
      });
      observer.observe(element);
  }

  openOffCanvas() {
    this.isOffCanvasOpen.set(true);
  }

  closeOffCanvas() {
    this.isOffCanvasOpen.set(false);
  }

  goToSaleDetail(saleId: number) {
    this.dataService.requestedSaleDetailId.set(saleId);
    this.dataService.requestedView.set('sales');
    this.closeOffCanvas();
  }

  setSalesmanChartMetric(metric: 'value' | 'unit') {
    this.salesmanChartMetric.set(metric);
  }

  setSalesTrendChartMetric(metric: 'value' | 'unit') {
    this.salesTrendChartMetric.set(metric);
  }

  setSalesByProjectChartMetric(metric: 'value' | 'unit') {
    this.salesByProjectChartMetric.set(metric);
  }

  setPlotInventoryChartMetric(metric: 'value' | 'unit') {
    this.plotInventoryChartMetric.set(metric);
  }

  setFilter(preset: DateRangePreset) {
    this.dataService.setActiveFilter(preset);
    if(preset !== 'custom') {
       this.dataService.setCustomDateRange('', '');
    }
  }

  updateCustomDate(type: 'start' | 'end', event: Event) {
    const value = (event.target as HTMLInputElement).value;
    const currentRange = this.dataService.customDateRange();
    const newRange = { ...currentRange, [type]: value };
    this.dataService.setCustomDateRange(newRange.start, newRange.end);
  }

  toggleCompare(event: Event) {
    const enabled = (event.target as HTMLInputElement).checked;
    this.dataService.setCompareEnabled(enabled);
  }

  // --- Forecast Methods ---
  toggleForecast(event: Event) {
    const enabled = (event.target as HTMLInputElement).checked;
    this.showForecast.set(enabled);
    if (enabled) {
      this.dataService.triggerForecast(this.activeFilter());
    } else {
      // Clear forecast data when disabled
      this.dataService.salesForecast.set(null);
      this.dataService.forecastError.set(null);
      this.dataService.forecastDescription.set(null);
    }
  }

  getPercentageChange(current: number, previous: number | null | undefined): number {
    if (previous === null || previous === undefined || previous === 0) {
      return current > 0 ? 100 : 0;
    }
    if (current === previous) return 0;
    return ((current - previous) / previous) * 100;
  }

  private positionTooltip(
    tooltip: any,
    targetElement: SVGGraphicsElement,
    containerElement: HTMLElement,
    htmlContent: string,
    yTransform: string = '-110%'
  ) {
      const targetRect = targetElement.getBoundingClientRect();
      const containerRect = containerElement.getBoundingClientRect();
  
      const xPos = targetRect.left - containerRect.left + targetRect.width / 2;
      const yPos = targetRect.top - containerRect.top;
  
      tooltip
          .html(htmlContent)
          .style("left", `${xPos}px`)
          .style("top", `${yPos}px`)
          .style("transform", `translate(-50%, ${yTransform})`);
  }

  private drawSalesTrendChart(
    historicalData: { label: string, value: number, count: number }[], 
    metric: 'value' | 'unit',
    forecastData: ForecastData[] | null
  ) {
    const element = this.salesTrendChartEl.nativeElement;
    d3.select(element).selectAll('*').remove();
    const self = this;
    const dataKey = metric === 'value' ? 'value' : 'count';
    const forecastKey = metric === 'value' ? 'proyeksi_penjualan' : 'unit_terjual';
    const forecastPessimisticKey = 'proyeksi_penjualan_pesimis';
    const forecastOptimisticKey = 'proyeksi_penjualan_optimis';

    const preset = this.dataService.activeFilter();
    let chartData = (preset === 'custom' || preset === 'all_time') 
        ? historicalData.filter(d => d.value > 0 || d.count > 0)
        : historicalData;
    
    if (!chartData || chartData.length === 0) {
        d3.select(element).append('p').attr('class', 'text-center text-gray-500 p-4').text('No sales data for this period.');
        return;
    }

    const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
    const formattedForecast = forecastData?.map(d => {
        const isQuarterly = d.bulan.includes('Q');
        let label = d.bulan;
        if (!isQuarterly) {
            const [year, month] = d.bulan.split('-');
            label = `${monthNamesShort[parseInt(month, 10) - 1]} '${year.slice(-2)}`;
        } else {
            const [year, quarter] = d.bulan.split('-');
            label = `${quarter} '${year.slice(-2)}`;
        }
        return {
            label: label,
            value: d[forecastKey as keyof ForecastData],
            pessimistic: d[forecastPessimisticKey as keyof ForecastData],
            optimistic: d[forecastOptimisticKey as keyof ForecastData],
        };
    }) || [];

    // Override forecast with jagged random-walk based on historical drift/volatility
    if (formattedForecast.length > 0 && chartData.length > 0) {
        this.buildJaggedForecast(chartData, formattedForecast, dataKey);
    }

    const allLabels = [...chartData.map(d => d.label), ...formattedForecast.map(d => d.label)];
    
    const tooltip = d3.select(element)
      .append("div")
      .attr("class", "absolute bg-gray-800 text-white p-2 rounded-md text-sm pointer-events-none z-10")
      .style("opacity", 0)
      .style("transition", "opacity 0.2s");
      
    const formatValue = (d: number) => {
        if (d >= 1e9) return "Rp" + d3.format(".2s")(d).replace(/G/, " M");
        if (d >= 1e6) return "Rp" + d3.format(".2s")(d).replace(/M/, " Jt");
        if (d >= 1e3) return "Rp" + d3.format(".2s")(d).replace(/k/, " Rb");
        return "Rp" + d;
    };

    const margin = { top: 20, right: 30, bottom: 80, left: 70 };
    const width = element.clientWidth - margin.left - margin.right;
    const height = 300 - margin.top - margin.bottom;

    const svg = d3.select(element).append('svg')
        .attr('width', '100%')
        .attr('height', '100%')
        .attr('viewBox', `0 0 ${element.clientWidth} ${300}`)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);
    
    const maxHistValue = d3.max(chartData, (d: any) => d[dataKey]) as number || 0;
    
    let maxForecastValue = d3.max(formattedForecast, (d: any) => d.value) as number || 0;
    if (metric === 'value') {
      const maxOptimistic = d3.max(formattedForecast, (d: any) => d.optimistic) as number || 0;
      maxForecastValue = Math.max(maxForecastValue, maxOptimistic);
    }

    const maxValue = Math.max(maxHistValue, maxForecastValue);
    
    const yDomainEnd = metric === 'unit' ? (maxValue > 0 ? d3.max([maxValue, 4]) : 1) : (maxValue * 1.1);
    const y = d3.scaleLinear().domain([0, yDomainEnd]).range([height, 0]);

    const yAxis = d3.axisLeft(y);
    if (metric === 'unit') {
        const tickCount = Math.min(Math.ceil(maxValue) + 1, 10);
        yAxis.ticks(tickCount).tickFormat(d3.format('d'));
    } else {
        yAxis.ticks(5).tickFormat(formatValue);
    }
    svg.append('g').call(yAxis);
        
    const x = d3.scaleBand().domain(allLabels).range([0, width]).padding(0.3);

    const xAxis = svg.append('g').attr('transform', `translate(0,${height})`).call(d3.axisBottom(x));
    xAxis.selectAll('text').attr('transform', 'translate(-10,0)rotate(-45)').style('text-anchor', 'end');

    svg.selectAll('.bar')
      .data(chartData)
      .enter()
      .append('rect')
        .attr('class', 'bar')
        .attr('x', (d: any) => x(d.label) as number)
        .attr('y', (d: any) => y(d[dataKey]))
        .attr('width', x.bandwidth())
        .attr('height', (d: any) => height - y(d[dataKey]))
        .attr('fill', '#7F0009')
        .attr('rx', 2)
        .on("mouseover", function(event: any, d: any) {
            tooltip.style("opacity", 1);
            d3.select(this).attr("fill", "#c82a32");
            const valueText = metric === 'value' ? formatValue(d.value) : `${d.count} unit${d.count > 1 ? 's' : ''}`;
            const html = `<strong>${valueText}</strong><br>${d.label}`;
            self.positionTooltip(tooltip, this as SVGGraphicsElement, element, html);
        })
        .on("mouseout", function() {
            tooltip.style("opacity", 0);
            d3.select(this).attr("fill", "#7F0009");
        });

    if (formattedForecast.length > 0) {
        const lastHistPoint = chartData[chartData.length - 1];
        const lineData = [{
          label: lastHistPoint.label,
          value: lastHistPoint[dataKey],
          pessimistic: lastHistPoint[dataKey],
          optimistic: lastHistPoint[dataKey],
        }, ...formattedForecast];

        // Draw per-segment colored lines (green for up, red for down)
        for (let i = 1; i < lineData.length; i++) {
          const prev = lineData[i - 1];
          const curr = lineData[i];
          const color = curr.value >= prev.value ? '#0F9D58' : '#DB4437';
          svg.append('line')
            .attr('x1', (x(prev.label) as number) + x.bandwidth() / 2)
            .attr('y1', y(prev.value))
            .attr('x2', (x(curr.label) as number) + x.bandwidth() / 2)
            .attr('y2', y(curr.value))
            .attr('stroke', color)
            .attr('stroke-width', 2.5)
            .attr('fill', 'none');
        }

        // Forecast dots with directional color
        svg.selectAll(".forecast-dot")
          .data(formattedForecast)
          .enter().append("circle")
            .attr("class", "forecast-dot")
            .attr("cx", d => (x(d.label) as number) + x.bandwidth() / 2)
            .attr("cy", d => y(d.value))
            .attr("r", 5)
            .attr("fill", (d, idx) => {
              const prev = lineData[idx]; // lineData includes lastHistPoint at index 0
              return d.value >= prev.value ? '#0F9D58' : '#DB4437';
            })
            .attr("stroke", "#0b1a2b")
            .attr("stroke-width", 1.2)
            .on("mouseover", function(event: any, d: any) {
                tooltip.style("opacity", 1);
                const valueText = metric === 'value' ? formatValue(d.value) : `${d.value} unit${d.value > 1 ? 's' : ''}`;
                const html = `<strong>Proyeksi: ${valueText}</strong><br>${d.label}`;
                self.positionTooltip(tooltip, this as SVGGraphicsElement, element, html, '-120%');
            })
            .on("mouseout", () => tooltip.style("opacity", 0));
    }
  }

  private buildJaggedForecast(
    historical: { label: string; value: number; count: number }[],
    forecast: any[],
    dataKey: 'value' | 'count'
  ) {
    const historyValues = historical.map(d => d[dataKey]).filter(v => typeof v === 'number' && !isNaN(v));
    if (historyValues.length < 2) return;

    const changes: number[] = [];
    for (let i = 1; i < historyValues.length; i++) {
      changes.push(historyValues[i] - historyValues[i - 1]);
    }
    const drift = changes.reduce((s, v) => s + v, 0) / changes.length || 0;
    const mean = drift;
    const variance =
      changes.reduce((s, v) => s + Math.pow(v - mean, 2), 0) /
      (changes.length || 1);
    const volatility = Math.sqrt(variance) || Math.abs(drift) * 0.6 || Math.max(historyValues[historyValues.length - 1] * 0.05, 1);

    let last = historyValues[historyValues.length - 1];
    const randNorm = d3.randomNormal.source(d3.randomLcg(Math.random()))(0, 1);

    forecast.forEach((f: any) => {
      const noise = randNorm();
      const step = drift + volatility * noise;
      let next = last + step;
      if (dataKey === 'count') {
        next = Math.max(0, Math.round(next));
      } else {
        next = Math.max(0, next);
      }
      f.value = next;
      if (dataKey === 'value') {
        f.pessimistic = Math.max(0, next - 0.6 * volatility);
        f.optimistic = Math.max(0, next + 0.6 * volatility);
      }
      last = next;
    });
  }

  private drawSalesBySalesmanChart(data: { name: string, value: number }[], metric: 'value' | 'unit') {
    const element = this.salesBySalesmanChartEl.nativeElement;
    d3.select(element).selectAll('*').remove();
    const self = this;
    
    if (!data || data.length === 0) {
        d3.select(element).append('p').attr('class', 'text-center text-gray-500 p-4').text('No data.');
        return;
    }
    
    const tooltip = d3.select(element)
        .append("div")
        .attr("class", "absolute bg-gray-800 text-white p-2 rounded-md text-sm pointer-events-none z-10")
        .style("opacity", 0)
        .style("transition", "opacity 0.2s");

    data.sort((a,b) => b.value - a.value);

    const margin = { top: 20, right: 20, bottom: 70, left: 70 };
    const width = element.clientWidth - margin.left - margin.right;
    const height = 300 - margin.top - margin.bottom;

    const svg = d3.select(element).append('svg')
      .attr('width', '100%')
      .attr('height', '100%')
      .attr('viewBox', `0 0 ${element.clientWidth} ${300}`)
      .append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    const x = d3.scaleBand()
      .range([0, width])
      .domain(data.map(d => d.name))
      .padding(0.3);

    const formatValue = (d: number) => {
        if (d >= 1e9) return "Rp" + d3.format(".2s")(d).replace(/G/, " M");
        if (d >= 1e6) return "Rp" + d3.format(".2s")(d).replace(/M/, " Jt");
        if (d >= 1e3) return "Rp" + d3.format(".2s")(d).replace(/k/, " Rb");
        return "Rp" + d;
    };
      
    const maxValue = d3.max(data, (d: any) => d.value) as number;
    const yDomainEnd = metric === 'unit' ? (maxValue > 0 ? maxValue : 1) : (maxValue * 1.1);
    const y = d3.scaleLinear()
        .domain([0, yDomainEnd])
        .range([height, 0]);
      
    svg.append('g')
      .attr('transform', `translate(0,${height})`)
      .call(d3.axisBottom(x))
      .selectAll('text')
        .attr('transform', 'translate(-10,0)rotate(-45)')
        .style('text-anchor', 'end');

    const yAxis = d3.axisLeft(y);
    if (metric === 'unit') {
        const tickCount = maxValue > 0 ? Math.min(maxValue + 1, 10) : 2;
        yAxis.ticks(tickCount).tickFormat(d3.format('d'));
    } else {
        yAxis.ticks(5).tickFormat(formatValue);
    }
    svg.append('g').call(yAxis);
      
    svg.selectAll('rect')
      .data(data)
      .enter()
      .append('rect')
        .attr('x', (d: any) => x(d.name) as number)
        .attr('y', (d: any) => y(d.value))
        .attr('width', x.bandwidth())
        .attr('height', (d: any) => height - y(d.value))
        .attr('fill', '#7F0009')
        .attr('rx', 3)
        .on("mouseover", function(event: any, d: any) {
            tooltip.style("opacity", 1);
            d3.select(this).attr("fill", "#c82a32");
            const valueText = metric === 'value' 
                ? formatValue(d.value) 
                : `${d.value} unit${d.value > 1 ? 's' : ''}`;
            const html = `<strong>${d.name}</strong><br>${valueText}`;
            self.positionTooltip(tooltip, this as SVGGraphicsElement, element, html);
        })
        .on("mouseout", function() {
            tooltip.style("opacity", 0);
            d3.select(this).attr("fill", "#7F0009");
        });
  }

  private wrapPieChartLabels(selection: any, maxLabelWidth: number) {
    selection.each(function(this: SVGTextElement, d: any) {
        const text = d3.select(this);
        text.text(null);
        
        const words = d.data.name.split(/\s+/).reverse();
        let word;
        let line: string[] = [];
        let lineNumber = 0;
        const lineHeight = 1.1; // ems
        
        let tspan = text.append("tspan").attr("x", 0).attr("dy", "0em");

        while (word = words.pop()) {
            line.push(word);
            tspan.text(line.join(" "));
            if (tspan.node()!.getComputedTextLength() > maxLabelWidth && line.length > 1) {
                line.pop();
                tspan.text(line.join(" "));
                line = [word];
                lineNumber++;
                tspan = text.append("tspan").attr("x", 0).attr("dy", `${lineHeight}em`).text(word);
            }
        }
        
        if (lineNumber > 0) {
            text.attr("dy", `-${(lineNumber * lineHeight) / 2}em`);
        }
    });
  }

  private drawSalesByProjectChart(data: { name: string, value: number }[], metric: 'value' | 'unit') {
    const element = this.salesByProjectChartEl.nativeElement;
    d3.select(element).selectAll('*').remove();

     if (!data || data.length === 0) {
        d3.select(element).append('p').attr('class', 'text-center text-gray-500 p-4').text('No data.');
        return;
    }
    
    const tooltip = d3.select(element)
        .append("div")
        .attr("class", "absolute bg-gray-900 text-white p-2 rounded-md text-sm pointer-events-none z-10")
        .style("opacity", 0)
        .style("transition", "opacity 0.2s");

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    };

    const width = element.clientWidth;
    const height = 300;
    const radius = Math.min(width, height) / 2 - 10;

    const svg = d3.select(element).append('svg')
      .attr('width', '100%')
      .attr('height', '100%')
      .attr('viewBox', `0 0 ${width} ${height}`)
      .append('g')
      .attr('transform', `translate(${width / 2},${height / 2})`);

    const color = d3.scaleOrdinal(d3.schemeTableau10);
    
    const pie = d3.pie()
      .value((d: any) => d.value)
      .sort(null);

    const data_ready = pie(data);

    const arc = d3.arc().innerRadius(radius * 0.5).outerRadius(radius * 0.8);

    svg
      .selectAll('path')
      .data(data_ready)
      .join('path')
      .attr('d', arc)
      .attr('fill', (d: any) => color(d.data.name))
      .attr('stroke', 'white')
      .style('stroke-width', '2px')
      .style('transition', 'fill 0.2s ease-in-out')
      .on('mouseover', function(event: any, d: any) {
        tooltip.style('opacity', 1);
        d3.select(this)
          .transition()
          .duration(200)
          .attr('fill', d3.color(color(d.data.name)).darker(0.7));
        
        const valueText = metric === 'value'
            ? formatCurrency(d.data.value)
            : `${d.data.value} unit${d.data.value > 1 ? 's' : ''}`;

        const htmlContent = `
            <div class="font-bold">${d.data.name}</div>
            <div>${valueText}</div>
        `;
        
        const [x, y] = arc.centroid(d);
        const xPos = x + width / 2;
        const yPos = y + height / 2;

        tooltip
            .html(htmlContent)
            .style("left", `${xPos}px`)
            .style("top", `${yPos}px`)
            .style("transform", "translate(-50%, -110%)");
      })
      .on('mouseout', function(event: any, d: any) {
        tooltip.style('opacity', 0);
        d3.select(this)
          .transition()
          .duration(200)
          .attr('fill', color(d.data.name));
      });

    const isDarkMode = this.themeService.isDarkMode();
    const labelColor = isDarkMode ? '#E5E7EB' : '#1F2937'; // gray-200 and gray-800

    // Add labels
    const arcLabel = d3.arc().innerRadius(radius * 0.95).outerRadius(radius * 0.95);

    const labels = svg.selectAll('text.pie-label')
      .data(data_ready)
      .join('text')
      .attr('class', 'pie-label')
      .attr('transform', (d: any) => `translate(${arcLabel.centroid(d)})`)
      .style('text-anchor', 'middle')
      .style('font-size', '12px')
      .style('fill', labelColor)
      .style('pointer-events', 'none');
      
    this.wrapPieChartLabels(labels, 90);
  }

  private drawPlotInventoryChart(data: { name: string, value: number }[], metric: 'value' | 'unit') {
    const element = this.plotInventoryChartEl.nativeElement;
    d3.select(element).selectAll('*').remove();

     if (!data || data.length === 0) {
        d3.select(element).append('p').attr('class', 'text-center text-gray-500 p-4').text('No available lots.');
        return;
    }

    const tooltip = d3.select(element)
        .append("div")
        .attr("class", "absolute bg-gray-900 text-white p-2 rounded-md text-sm pointer-events-none z-10")
        .style("opacity", 0)
        .style("transition", "opacity 0.2s");
    
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    };

    const width = element.clientWidth;
    const height = 300;
    const radius = Math.min(width, height) / 2 - 10;

    const svg = d3.select(element).append('svg')
      .attr('width', '100%')
      .attr('height', '100%')
      .attr('viewBox', `0 0 ${width} ${height}`)
      .append('g')
      .attr('transform', `translate(${width / 2},${height / 2})`);

    const color = d3.scaleOrdinal(d3.schemePastel2);
    
    const pie = d3.pie()
      .value((d: any) => d.value)
      .sort(null);

    const data_ready = pie(data);

    const arc = d3.arc().innerRadius(radius * 0.5).outerRadius(radius * 0.8);

    svg
      .selectAll('path')
      .data(data_ready)
      .join('path')
      .attr('d', arc)
      .attr('fill', (d: any) => color(d.data.name))
      .attr('stroke', 'white')
      .style('stroke-width', '2px')
      .style('transition', 'fill 0.2s ease-in-out')
      .on('mouseover', function(event: any, d: any) {
        tooltip.style('opacity', 1);
        d3.select(this)
          .transition()
          .duration(200)
          .attr('fill', d3.color(color(d.data.name)).darker(0.7));

        const valueText = metric === 'value'
            ? formatCurrency(d.data.value)
            : `${d.data.value} unit${d.data.value > 1 ? 's' : ''}`;

        const htmlContent = `
            <div class="font-bold">${d.data.name}</div>
            <div>${valueText}</div>
        `;
        
        const [x, y] = arc.centroid(d);
        const xPos = x + width / 2;
        const yPos = y + height / 2;

        tooltip
            .html(htmlContent)
            .style("left", `${xPos}px`)
            .style("top", `${yPos}px`)
            .style("transform", "translate(-50%, -110%)");
      })
      .on('mouseout', function(event: any, d: any) {
        tooltip.style('opacity', 0);
        d3.select(this)
          .transition()
          .duration(200)
          .attr('fill', color(d.data.name));
      });

    const isDarkMode = this.themeService.isDarkMode();
    const labelColor = isDarkMode ? '#E5E7EB' : '#1F2937'; // gray-200 and gray-800

    const arcLabel = d3.arc().innerRadius(radius * 0.95).outerRadius(radius * 0.95);

    const labels = svg.selectAll('text.pie-label')
      .data(data_ready)
      .join('text')
      .attr('class', 'pie-label')
      .attr('transform', (d: any) => `translate(${arcLabel.centroid(d)})`)
      .style('text-anchor', 'middle')
      .style('font-size', '12px')
      .style('fill', labelColor)
      .style('pointer-events', 'none');

    this.wrapPieChartLabels(labels, 90);
  }
}
