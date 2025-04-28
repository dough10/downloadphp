import { expect } from '@open-wc/testing';
import sinon from 'sinon';
import { calculatePercent, calculateSpeed } from '../Download.js';
import Download from '../Download.js';

describe('Download Class', () => {
  let responseMock;
  let readerMock;
  let abortControllerMock;

  beforeEach(() => {
    // Mock the response body reader
    readerMock = {
      read: sinon.stub(),
      cancel: sinon.spy(),
    };

    // Mock the response object
    responseMock = {
      body: {
        getReader: sinon.stub().returns(readerMock),
      },
    };

    // Mock the AbortController
    abortControllerMock = {
      abort: sinon.spy(),
    };
  });

  it('should calculate percentage correctly', () => {
    expect(calculatePercent(50, 100)).to.equal('50.0');
    expect(calculatePercent(0, 100)).to.equal('0.0');
    expect(calculatePercent(100, 100)).to.equal('100.0');
  });

  it('should calculate speed correctly', () => {
    expect(calculateSpeed(125000, 1000)).to.equal('1.0 mbps');
    expect(calculateSpeed(125, 1000)).to.equal('1.0 kbps');
    expect(calculateSpeed(12, 1000)).to.equal('96.0 bps');
    expect(calculateSpeed(0, 1000)).to.equal('0 bps');
  });

  it('should emit update events during download', async () => {
    // Stub the reader to simulate chunks being read
    readerMock.read
      .onFirstCall().resolves({ done: false, value: new Uint8Array(1000) })
      .onSecondCall().resolves({ done: false, value: new Uint8Array(2000) })
      .onThirdCall().resolves({ done: true });

    const download = new Download(responseMock, 3000, abortControllerMock);

    const updateSpy = sinon.spy();
    download.addEventListener('update', updateSpy);

    await download.start();

    expect(updateSpy.callCount).to.equal(3);
    expect(updateSpy.firstCall.args[0].detail).to.include({
      progress: '33.3',
    });
    expect(updateSpy.secondCall.args[0].detail).to.include({
      progress: '100.0',
    });
  });

  it('should emit finished event when download is complete', async () => {
    // Stub the reader to simulate chunks being read
    readerMock.read
      .onFirstCall().resolves({ done: false, value: new Uint8Array(3000) })
      .onSecondCall().resolves({ done: true });

    const download = new Download(responseMock, 3000, abortControllerMock);

    const finishedSpy = sinon.spy();
    download.addEventListener('finished', finishedSpy);

    await download.start();

    expect(finishedSpy.calledOnce).to.be.true;
    expect(finishedSpy.firstCall.args[0].detail).to.have.property('chunks').that.is.an('array');
  });

  it('should stop the download and emit stopped event', () => {
    const download = new Download(responseMock, 3000, abortControllerMock);

    const stoppedSpy = sinon.spy();
    download.addEventListener('stopped', stoppedSpy);

    download.stop();

    expect(abortControllerMock.abort.calledOnce).to.be.true;
    expect(readerMock.cancel.calledOnce).to.be.true;
    expect(stoppedSpy.calledOnce).to.be.true;
  });
});